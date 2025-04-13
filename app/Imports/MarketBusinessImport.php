<?php

namespace App\Imports;

use App\Models\MarketBusiness;
use App\Models\MarketUploadStatus;
use Exception;
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
        try {
            $errors = [];
            $rowNumber = 1;

            foreach ($records as $record) {
                $rowErrors = [];

                if (empty($record[21])) {
                    $rowErrors[] = "Nama Usaha kosong pada baris $rowNumber.";
                }
                if (empty($record[22])) {
                    $rowErrors[] = "Status Bangunan kosong pada baris $rowNumber.";
                }
                if (empty($record[24])) {
                    $rowErrors[] = "Deskripsi Usaha kosong pada baris $rowNumber.";
                }
                if (empty($record[26])) {
                    $rowErrors[] = "Sektor Usaha kosong pada baris $rowNumber.";
                }
                if (empty($record[4])) {
                    $rowErrors[] = "Latitude kosong pada baris $rowNumber.";
                }
                if (empty($record[5])) {
                    $rowErrors[] = "Longitude kosong pada baris $rowNumber.";
                }

                if (!empty($rowErrors)) {
                    $errors[$rowNumber] = $rowErrors;
                } else {
                    MarketBusiness::create([
                        'name' => $record[21],
                        'status' => $record[22],
                        'address' => $record[23],
                        'description' => $record[24],
                        'sector' => $record[26],
                        'note' => $record[25],

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
        } catch (Exception $e) {
            $this->status = $this->status->update([
                'status' => 'failed',
                'message' => $e->getMessage(),
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
