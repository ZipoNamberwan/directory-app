<?php

namespace App\Imports;

use App\Models\MarketBusiness;
use App\Models\MarketUploadStatus;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithStartRow;

class MarketBusinessImportSheet implements ToCollection, WithChunkReading, WithStartRow, ShouldQueue
{

    use Importable, Queueable;

    protected $status;

    public function __construct($statusId)
    {
        $this->status = MarketUploadStatus::find($statusId);
        $this->status->update([
            'status' => 'loading',
        ]);
    }

    /**
     * @param Collection $collection
     */
    public function collection(Collection $records)
    {
        $errors = [];
        $rowNumber = 1;

        foreach ($records as $record) {
            $rowErrors = [];

            for ($i = 21; $i <= 22; $i++) {
                if (empty($record[$i])) {
                    $rowErrors[] = "Kolom " . ($i + 1) . " kosong pada baris $rowNumber.";
                }
            }

            if (!empty($rowErrors)) {
                $errors[$rowNumber] = $rowErrors;
            } else {
                MarketBusiness::create([
                    'name' => $record[21],
                    'owner' => $record[22],
                    'note' => $record[23],
                    'latitude' => $record[4],
                    'longitude' => $record[5],
                    'market_id' => $this->status->market_id,
                    'user_id' => $this->status->user_id,
                    'upload_id' => $this->status->id,
                    'regency_id' => $this->status->regency_id
                ]);
            }

            $rowNumber++;
        }

        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $row => $messages) {
                foreach ($messages as $message) {
                    $errorMessages[] = $message;
                }
            }
            $this->status = $this->status->update([
                'message' => $this->status->message . implode("<br>", $errorMessages) . "<br>",
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

class MarketBusinessImport implements WithMultipleSheets, ShouldQueue, WithChunkReading
{
    use Importable, Queueable;

    protected $uuid;

    public function __construct($uuid)
    {
        $this->uuid = $uuid;
    }

    public function sheets(): array
    {
        return [
            0 => new MarketBusinessImportSheet($this->uuid),
        ];
    }

    public function chunkSize(): int
    {
        return 1000;
    }
}
