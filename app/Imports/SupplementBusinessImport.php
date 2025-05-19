<?php

namespace App\Imports;

use App\Models\SupplementBusiness;
use App\Models\SupplementUploadStatus;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithStartRow;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class SupplementBusinessImportSheet implements ToCollection, WithChunkReading, WithStartRow, ShouldQueue, WithHeadingRow
{

    use Importable, Queueable;

    protected $status;

    public function __construct($statusId)
    {
        $this->status = SupplementUploadStatus::find($statusId);
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
            $successCount = 0;
            $errors = [];
            $rowNumber = 1;
            $processedCount = 0;

            foreach ($records as $record) {
                $rowErrors = [];

                if (
                    empty($record['id'])
                    && empty($record['nama_usaha'])
                    && empty($record['status_bangunan_usaha'])
                    && empty($record['deskripsi_aktifitas'])
                    && empty($record['sektor'])
                    && empty($record['latitude'])
                    && empty($record['longitude'])
                ) {
                    continue; // Skip empty rows
                }

                $processedCount++;

                if (empty($record['nama_usaha'])) {
                    $rowErrors[] = "Nama Usaha kosong pada baris $rowNumber.";
                }
                $statusBangunan = $record['status_bangunan_usaha'] ?? $record['status_bangunan_usahate'] ?? null;
                if (empty($statusBangunan) || $statusBangunan === '-') {
                    $rowErrors[] = "Status Bangunan kosong pada baris $rowNumber.";
                }
                if (empty($record['deskripsi_aktifitas'])) {
                    $rowErrors[] = "Deskripsi Usaha kosong pada baris $rowNumber.";
                }
                if (empty($record['sektor']) || $record['sektor'] == '-') {
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
                    $inserted =  SupplementBusiness::create([
                        'name' => $record['nama_usaha'],
                        'status' => $record['status_bangunan_usaha'] ?? $record['status_bangunan_usahate'] ?? null,
                        'address' => $record['alamat_lengkap'],
                        'description' => $record['deskripsi_aktifitas'],
                        'sector' => $record['sektor'],
                        'note' => $record['catatan_lantaibloksektor'] ?? $record['catatan_lantaiblocksektor'] ?? null,

                        'latitude' => $record['latitude'],
                        'longitude' => $record['longitude'],

                        'user_id' => $this->status->user_id,
                        'upload_id' => $this->status->id,
                        'regency_id' => $this->status->regency_id,
                        'subdistrict_id' => $this->status->subdistrict_id,
                        'village_id' => $this->status->village_id,
                        'sls_id' => $this->status->sls_id,
                        'organization_id' => $this->status->organization_id,
                        'upload_id' => $this->status->id,
                    ]);

                    if ($inserted) {
                        $successCount++;
                    }
                }

                $rowNumber++;
            }

            $this->status->update([
                'processed_count' => $processedCount,
            ]);

            // 🧨 Case 1: File is completely empty (no rows processed)
            if ($processedCount === 0) {
                throw new Exception('File kosong atau tidak memiliki baris yang dapat diproses.');
            }

            // ⚠️ Case 2: File has rows but all were invalid
            if ($successCount === 0 && $processedCount > 0) {
                $errorMessages = [];
                foreach ($errors as $row => $messages) {
                    foreach ($messages as $message) {
                        $errorMessages[] = $message;
                    }
                }

                throw new Exception(implode("<br>", $errorMessages));
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
        } catch (Exception $e) {
            $maxLength = 10000;

            $this->status->update([
                'status' => 'failed',
                'message' => Str::limit($e->getMessage(), $maxLength),
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

class SupplementBusinessImport implements WithMultipleSheets, ShouldQueue, WithChunkReading
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
            0 => new SupplementBusinessImportSheet($this->uuid),
        ];
    }

    public function chunkSize(): int
    {
        return 1000;
    }
}
