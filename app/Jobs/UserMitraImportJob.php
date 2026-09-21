<?php

namespace App\Jobs;

use App\Helpers\DatabaseSelector;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class UserMitraImportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 30;
    public int $timeout = 120;

    /**
     * @param array $header  Excel header row
     * @param array $rows    Array of row arrays, each matching $header order
     */
    public function __construct(
        public array $header,
        public array $rows,
        public string $organizationId,
        public ?string $regencyId,
    ) {}

    public function handle(): void
    {
        $header = array_map(fn ($h) => strtolower(trim((string) $h)), $this->header);

        // Turn raw rows into associative arrays keyed by the (lowercased) header
        $records = collect($this->rows)
            ->filter(fn ($row) => count($row) === count($header))
            ->map(fn ($row) => array_combine($header, $row));

        $malformedRows = count($this->rows) - $records->count();

        Log::debug('UserMitraImportJob: chunk received', [
            'row_count' => count($this->rows),
            'malformed_rows' => $malformedRows,
            'header' => $header,
        ]);

        if ($records->isEmpty()) {
            return;
        }

        $parsed = [];
        $statusCounts = [];
        $skippedMissingEmail = 0;

        foreach ($records as $r) {
            $ketStatus = trim((string) ($r['ket_status'] ?? ''));
            $statusCounts[$ketStatus] = ($statusCounts[$ketStatus] ?? 0) + 1;

            if ($ketStatus !== 'Diterima') {
                continue;
            }

            // Lowercased so PHP-side dedup agrees with the DB's case-insensitive email unique index
            $email = strtolower(trim((string) ($r['mitra_detail.email'] ?? $r['email'] ?? '')));
            $name = trim((string) ($r['mitra_detail.nama_lengkap'] ?? $r['nama_lengkap'] ?? ''));
            $namaPos = trim((string) ($r['nama_pos'] ?? ''));

            if ($email === '') {
                $skippedMissingEmail++;
                continue;
            }

            if (str_contains($namaPos, 'PPL')) {
                $role = 'pcl';
            } elseif (str_contains($namaPos, 'PML')) {
                $role = 'pml';
            } else {
                $role = 'pcl';
            }

            $parsed[] = [
                'email' => $email,
                'firstname' => $name !== '' ? $name : null,
                'role' => $role,
            ];
        }

        Log::debug('UserMitraImportJob: ket_status breakdown', $statusCounts);

        if (empty($parsed)) {
            return;
        }

        // Every created user gets replicated to every regional connection
        // (User::syncToOtherDatabases(), matched by id — not email), so an email that
        // already exists in ANY of them will make that replication throw a duplicate-key
        // error even though it's missing from the default connection. Check them all.
        $emails = array_unique(array_column($parsed, 'email'));
        $existingEmails = collect();

        foreach (DatabaseSelector::getListConnections() as $connection) {
            $existingEmails = $existingEmails->merge(
                User::on($connection)->whereIn('email', $emails)->pluck('email')
            );
        }

        $existingEmails = $existingEmails->map(fn ($email) => strtolower($email))->flip();

        $seen = [];
        $skippedAlreadyExists = 0;
        $skippedDuplicateInChunk = 0;
        $created = 0;

        foreach ($parsed as $row) {
            $email = $row['email'];

            if (isset($existingEmails[$email])) {
                $skippedAlreadyExists++;
                continue;
            }

            if (isset($seen[$email])) {
                $skippedDuplicateInChunk++;
                continue;
            }

            $seen[$email] = true;

            // Users are created one at a time (not bulk-inserted) so that a failure on
            // any single row — including during its cross-database replication or role
            // sync — is caught and logged here instead of throwing out of the loop and
            // silently dropping every row still left in this chunk.
            try {
                $user = User::create([
                    'username' => $email,
                    'email' => $email,
                    'firstname' => $row['firstname'],
                    'is_kendedes_user' => true,
                    'organization_id' => $this->organizationId,
                    'regency_id' => $this->regencyId,
                    'password' => Hash::make(Str::random(16)),
                ]);

                $user->assignRoleAllDatabase($row['role']);
            } catch (Throwable $e) {
                Log::warning("UserMitraImportJob: failed to create user for {$email}: " . $e->getMessage());
                continue;
            }

            $created++;
        }

        Log::info("UserMitraImportJob chunk summary: rows={$records->count()}, skipped_missing_email={$skippedMissingEmail}, skipped_already_exists={$skippedAlreadyExists}, skipped_duplicate_in_chunk={$skippedDuplicateInChunk}, created={$created}");
    }

    public function failed(Throwable $e): void
    {
        Log::error('UserMitraImportJob failed: ' . $e->getMessage());
    }
}
