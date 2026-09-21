<?php

namespace App\Jobs;

use App\Models\Sls;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class UserSlsCensusImportJob implements ShouldQueue
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
    ) {}

    public function handle(): void
    {
        $header = array_map(fn ($h) => strtolower(trim((string) $h)), $this->header);

        // Turn raw rows into associative arrays keyed by the (lowercased) header
        $records = collect($this->rows)
            ->filter(fn ($row) => count($row) === count($header))
            ->map(fn ($row) => array_combine($header, $row));

        if ($records->isEmpty()) {
            return;
        }

        $parsed = [];

        foreach ($records as $r) {
            $provCode = trim((string) ($r['prov_code'] ?? ''));
            $kabCode = trim((string) ($r['kab_code'] ?? ''));
            $kecCode = trim((string) ($r['kec_code'] ?? ''));
            $desaCode = trim((string) ($r['desa_code'] ?? ''));
            $slsCode = trim((string) ($r['subsls_code'] ?? $r['sls_code'] ?? ''));
            // Lowercased so PHP-side dedup (array_unique/array_diff) agrees with the DB's
            // case-insensitive email unique index — otherwise Foo@x.com and foo@x.com
            // look like two distinct emails here but the same row in the database.
            $email = strtolower(trim((string) ($r['email'] ?? $r['username'] ?? '')));

            if ($provCode === '' || $kabCode === '' || $kecCode === '' || $desaCode === '' || $slsCode === '' || $email === '') {
                continue;
            }

            // Zero-padded to each area level's fixed width: Excel stores these codes as
            // plain numbers in some source files (e.g. kab_code 1 instead of "01"), which
            // silently drops leading zeros and produces a long_code that matches nothing.
            $longCode = str_pad($provCode, 2, '0', STR_PAD_LEFT)
                . str_pad($kabCode, 2, '0', STR_PAD_LEFT)
                . str_pad($kecCode, 3, '0', STR_PAD_LEFT)
                . str_pad($desaCode, 3, '0', STR_PAD_LEFT)
                . substr(str_pad($slsCode, 6, '0', STR_PAD_LEFT), 0, 4)
                . '00';

            $parsed[] = [
                'long_code' => $longCode,
                'email' => $email,
            ];
        }

        if (empty($parsed)) {
            return;
        }

        // Resolve SLS ids for the unique long codes found in this chunk
        $longCodes = array_unique(array_column($parsed, 'long_code'));
        $slsMap = Sls::whereIn('long_code', $longCodes)->pluck('id', 'long_code');

        // Resolve users for the unique (lowercased) emails found in this chunk.
        // Keyed by lowercased email too, since the DB row's stored casing may differ.
        $emails = array_unique(array_column($parsed, 'email'));
        $userMap = User::whereIn('email', $emails)
            ->get(['id', 'email'])
            ->mapWithKeys(fn ($user) => [strtolower($user->email) => $user->id]);

        $candidates = [];
        $failed = [];

        foreach ($parsed as $row) {
            $slsId = $slsMap[$row['long_code']] ?? null;
            $userId = $userMap[$row['email']] ?? null;

            if (!$slsId || !$userId) {
                $failed[] = [$row['email'], $row['long_code']];
                continue;
            }

            $candidates[] = [
                'user_id' => $userId,
                'sls_id' => $slsId,
            ];
        }

        $this->insertMissingCensusRows($candidates);
        $this->logFailedRows($failed);
    }

    /**
     * @param array<int, array{0: string, 1: string}> $failed
     */
    protected function logFailedRows(array $failed): void
    {
        if (empty($failed)) {
            return;
        }

        $path = storage_path('../backup/allocation/failed.csv');

        // Suppressed: a filesystem/permission error here would otherwise surface as a
        // PHP warning that Laravel's error handler promotes into a thrown ErrorException,
        // failing the whole job over what should just be a best-effort log write.
        $handle = @fopen($path, 'a');

        if (!$handle) {
            Log::error("UserSlsCensusImportJob: unable to open failed log file at {$path}");
            return;
        }

        flock($handle, LOCK_EX);

        if (fstat($handle)['size'] === 0) {
            fputcsv($handle, ['email', 'long_code']);
        }

        foreach ($failed as $row) {
            fputcsv($handle, $row);
        }

        flock($handle, LOCK_UN);
        fclose($handle);
    }

    /**
     * @param array<int, array{user_id: string, sls_id: string}> $candidates
     */
    protected function insertMissingCensusRows(array $candidates): int
    {
        if (empty($candidates)) {
            return 0;
        }

        $userIds = array_unique(array_column($candidates, 'user_id'));

        $existing = DB::table('user_sls_census')
            ->whereIn('user_id', $userIds)
            ->get(['user_id', 'sls_id'])
            ->map(fn ($row) => $row->user_id . '|' . $row->sls_id)
            ->flip();

        $rows = [];
        $seen = [];

        foreach ($candidates as $candidate) {
            $key = $candidate['user_id'] . '|' . $candidate['sls_id'];

            if (isset($existing[$key]) || isset($seen[$key])) {
                continue;
            }

            $seen[$key] = true;

            $rows[] = [
                'id' => (string) Str::uuid(),
                'user_id' => $candidate['user_id'],
                'sls_id' => $candidate['sls_id'],
            ];
        }

        if (!empty($rows)) {
            DB::table('user_sls_census')->insert($rows);
        }

        return count($rows);
    }

    public function failed(Throwable $e): void
    {
        Log::error('UserSlsCensusImportJob failed: ' . $e->getMessage());
    }
}
