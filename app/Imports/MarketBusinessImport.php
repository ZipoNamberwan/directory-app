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
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithStartRow;

class MarketBusinessImportSheet implements ToCollection, WithChunkReading, WithStartRow, ShouldQueue, WithHeadingRow
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
        if ($this->status->market->completion_status != 'done') {
            try {
                $errors = [];
                $rowNumber = 1;

                foreach ($records as $record) {
                    $rowErrors = [];

                    if (empty($record['nama_usaha'])) {
                        $rowErrors[] = "Nama Usaha kosong pada baris $rowNumber.";
                    }
                    if (empty($record['status_bangunan_usaha'] ?? $record['status_bangunan_usahate'] ?? null)) {
                        $rowErrors[] = "Status Bangunan kosong pada baris $rowNumber.";
                    }
                    if (empty($record['deskripsi_aktifitas'])) {
                        $rowErrors[] = "Deskripsi Usaha kosong pada baris $rowNumber.";
                    }
                    if (empty($record['sektor'])) {
                        $rowErrors[] = "Sektor Usaha kosong pada baris $rowNumber.";
                    }
                    if (empty($record['latitude'])) {
                        $rowErrors[] = "Latitude kosong pada baris $rowNumber.";
                    }
                    if (empty($record['longitude'])) {
                        $rowErrors[] = "Longitude kosong pada baris $rowNumber.";
                    }

                    if (!empty($rowErrors)) {
                        $errors[$rowNumber] = $rowErrors;
                    } else {
                        MarketBusiness::create([
                            'name' => $record['nama_usaha'],
                            'status' => $record['status_bangunan_usaha'] ?? $record['status_bangunan_usahate'] ?? null,
                            'address' => $record['alamat_lengkap'],
                            'description' => $record['deskripsi_aktifitas'],
                            'sector' => $record['sektor'],
                            'note' => $record['catatan_lantaibloksektor'] ?? $record['catatan_lantaiblocksektor'] ?? null,

                            'latitude' => $record['latitude'],
                            'longitude' => $record['longitude'],
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
                    $this->status->update([
                        'message' => $this->status->message . implode("<br>", $errorMessages) . "<br>",
                    ]);
                }

                $this->status->market->update([
                    'completion_status' => 'on going',
                ]);

            } catch (Exception $e) {
                $this->status->update([
                    'status' => 'failed',
                    'message' => $e->getMessage(),
                ]);
            }
        } else {
            $this->status->update([
                'status' => 'failed',
                'message' => 'Tidak bisa mengupload data, karena status pasar sudah selesai/completed. Hubungi Admin Kab untuk membuka kembali.',
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
