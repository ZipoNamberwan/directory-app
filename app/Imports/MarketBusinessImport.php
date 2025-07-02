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
use Illuminate\Support\Str;

class MarketBusinessImportSheet implements
    ToCollection,
    WithChunkReading,
    WithStartRow,
    ShouldQueue,
    WithHeadingRow
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
        if (
            $this->status->market->completion_status != 'done' &&
            $this->status->market->target_category != 'non target'
        ) {
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
                    $allowedBuildingStatus = ['Tetap', 'Tidak Tetap'];
                    $buildingStatus = $record['status_bangunan_usaha'] ?? $record['status_bangunan_usahate'] ?? null;
                    if (empty($buildingStatus) || $buildingStatus === '-') {
                        $rowErrors[] = "Status Bangunan kosong pada baris $rowNumber.";
                    } elseif (!in_array($buildingStatus, $allowedBuildingStatus)) {
                        $rowErrors[] = "Status Bangunan tidak valid pada baris $rowNumber. Hanya diperbolehkan: " . implode(', ', $allowedBuildingStatus) . ".";
                    }

                    if (empty($record['deskripsi_aktifitas'])) {
                        $rowErrors[] = "Deskripsi Usaha kosong pada baris $rowNumber.";
                    }

                    $allowedSectorPrefixes = ['A.', 'B.', 'C.', 'D.', 'E.', 'F.', 'G.', 'H.', 'I.', 'J.', 'K.', 'L.', 'M.', 'N.', 'O.', 'P.', 'Q.', 'R.', 'S.', 'T.', 'U.'];
                    $sector = $record['sektor'] ?? null;
                    if (empty($sector) || $sector === '-') {
                        $rowErrors[] = "Sektor Usaha kosong pada baris $rowNumber.";
                    } elseif (!collect($allowedSectorPrefixes)->contains(fn($prefix) => str_starts_with($sector, $prefix))) {
                        $rowErrors[] = "Sektor Usaha tidak valid pada baris $rowNumber. Harus diawali dengan salah satu dari: " . implode(', ', $allowedSectorPrefixes) . ".";
                    }
                    if (empty($record['latitude'])) {
                        $rowErrors[] = "Latitude kosong pada baris $rowNumber.";
                    } else {
                        if (!is_numeric($record['latitude'])) {
                            $rowErrors[] = "Latitude tidak valid (bukan angka) pada baris $rowNumber.";
                        } else {
                            $latitude = (float)$record['latitude'];

                            if ($latitude < -90 || $latitude > 90) {
                                $rowErrors[] = "Latitude di luar rentang yang diperbolehkan (-90 sampai 90) pada baris $rowNumber.";
                            }

                            if (!$this->validateDecimalPrecision($record['latitude'], 2, 10)) {
                                $rowErrors[] = "Latitude pada baris $rowNumber memiliki terlalu banyak angka. Maksimal 2 digit sebelum titik desimal dan 10 digit setelah titik desimal.";
                            }

                            if ($latitude > 0) {
                                $rowErrors[] = "Latitude pada baris $rowNumber harus bernilai negatif.";
                            }
                        }
                    }

                    if (empty($record['longitude'])) {
                        $rowErrors[] = "Longitude kosong pada baris $rowNumber.";
                    } else {
                        if (!is_numeric($record['longitude'])) {
                            $rowErrors[] = "Longitude tidak valid (bukan angka) pada baris $rowNumber.";
                        } else {
                            $longitude = (float)$record['longitude'];

                            if ($longitude < -180 || $longitude > 180) {
                                $rowErrors[] = "Longitude di luar rentang yang diperbolehkan (-180 sampai 180) pada baris $rowNumber.";
                            }

                            if (!$this->validateDecimalPrecision($record['longitude'], 3, 10)) {
                                $rowErrors[] = "Longitude pada baris $rowNumber memiliki terlalu banyak angka. Maksimal 3 digit sebelum titik desimal dan 10 digit setelah titik desimal.";
                            }
                        }
                    }

                    if (!empty($rowErrors)) {
                        $errors[$rowNumber] = $rowErrors;
                    } else {
                        $inserted = MarketBusiness::create([
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

                        // Only count if insert returns a valid object
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

                // ✅ Case 3: At least one successful insert
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
                $maxLength = 10000;

                $this->status->update([
                    'status' => 'failed',
                    'message' => Str::limit($e->getMessage(), $maxLength),
                ]);
            }
        } else {
            if ($this->status->market->completion_status == 'done') {
                $this->status->update([
                    'status' => 'failed',
                    'message' => 'Tidak bisa mengupload data, karena status pasar sudah selesai/completed. Hubungi Admin Kab untuk membuka kembali.',
                ]);
            }
            if ($this->status->market->target_category == 'non target') {
                $this->status->update([
                    'status' => 'failed',
                    'message' => 'Tidak bisa mengupload data, karena kategori pasar adalah non target. Hubungi Admin Kab untuk membuka kembali.',
                ]);
            }
        }
    }

    function validateDecimalPrecision($value, $maxDigitsBeforeDot, $maxDigitsAfterDot): bool
    {
        if (!is_numeric($value)) {
            return false;
        }

        $parts = explode('.', (string)$value);
        $beforeDot = ltrim($parts[0], '-');
        $afterDot = isset($parts[1]) ? $parts[1] : '';

        return strlen($beforeDot) <= $maxDigitsBeforeDot && strlen($afterDot) <= $maxDigitsAfterDot;
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
