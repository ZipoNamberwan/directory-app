<?php

namespace App\Imports;

use App\Models\MarketAssignmentStatus;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use App\Jobs\ImportAssignmentJob;
use App\Models\Market;
use App\Models\User;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithStartRow;

class MarketAssignmentImportSheet implements ToCollection, WithChunkReading, WithStartRow, ShouldQueue
{
    use Importable, Queueable;

    protected $uuid;
    protected $regency;

    public function __construct($regency, $uuid)
    {
        $this->uuid = $uuid;
        $this->regency = $regency;
    }

    /**
     * @param Collection $collection
     */
    public function collection(Collection $rows)
    {
        $errors = [];

        foreach ($rows as $index => $row) {
            $rowNumber = $index + 1;
            $email = $row[0] ?? null;
            $marketId = $row[1] ?? null;

            $user = User::where('email', $email)->first();
            $market = Market::find($marketId);

            if (!$user) {
                $errors[] = "Baris ke {$rowNumber}: User (ID: {$email}) tidak ditemukan.";
                continue;
            }

            if (!$market) {
                $errors[] = "Baris ke {$rowNumber}: Market (ID: {$marketId}) tidak ditemukan.";
                continue;
            }

            $user->markets()->syncWithoutDetaching([
                $marketId => [
                    'user_firstname' => $user->firstname ?? '',
                    'market_name' => $market->name ?? '',
                ]
            ]);
        }

        if (count($errors)) {
            $status = MarketAssignmentStatus::find($this->uuid);
            $status->update([
                'message' => $status->message . implode("<br>", $errors) . "<br>",
            ]);
        }
    }

    public function chunkSize(): int
    {
        return 1000;
    }

    public function startRow(): int
    {
        return 2;
    }
}


class MarketAssignmentImport implements WithMultipleSheets, ShouldQueue, WithChunkReading
{
    use Importable, Queueable;

    protected $uuid;
    protected $regency;

    public function __construct($regency, $uuid)
    {
        $this->uuid = $uuid;
        $this->regency = $regency;

        MarketAssignmentStatus::find($uuid)->update([
            'status' => 'loading',
        ]);
    }

    public function sheets(): array
    {
        return [
            0 => new MarketAssignmentImportSheet($this->regency, $this->uuid),
        ];
    }

    public function chunkSize(): int
    {
        return 1000;
    }
}
