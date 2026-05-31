<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\KbliStatistic;
use App\Models\Subdistrict;
use App\Models\Village;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Illuminate\Support\Facades\DB;

class ImportKbliStatisticsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:import-statistics';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import KBLI statistics from backup/statistics.xlsx';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $path = base_path('backup/statistics.xlsx');
        if (!file_exists($path)) {
            $path = '/backup/statistics.xlsx';
        }

        if (!file_exists($path)) {
            $this->error("File not found at backup/statistics.xlsx or /backup/statistics.xlsx");
            return 1;
        }

        $this->info("Reading statistics file from: {$path}");

        // Load mappings from default database connection (only active periods)
        $this->info("Loading subdistricts and villages mappings...");
        $subdistrictMap = Subdistrict::whereHas('period', function ($query) {
            $query->where('is_active', true);
        })->pluck('id', 'long_code')->toArray();

        $villageMap = Village::whereHas('period', function ($query) {
            $query->where('is_active', true);
        })->pluck('id', 'long_code')->toArray();

        $this->info("Found " . count($subdistrictMap) . " subdistricts and " . count($villageMap) . " villages in database.");

        $this->info("Loading spreadsheet...");
        $spreadsheet = IOFactory::load($path);

        $insertData = [];

        // 1. Process Subdistrict sheet
        $subdistrictSheet = $spreadsheet->getSheetByName('subdistrict');
        if ($subdistrictSheet) {
            $this->info("Processing 'subdistrict' sheet...");
            $rows = $subdistrictSheet->toArray(null, true, false, true);
            $header = array_shift($rows);

            $subdistrictCol = null;
            $codeCol = null;
            $countCol = null;
            $descriptionCol = null;

            foreach ($header as $colLetter => $headerName) {
                $headerName = strtolower(trim($headerName));
                if ($headerName === 'subdistrict' || $headerName === 'subdistric') {
                    $subdistrictCol = $colLetter;
                } elseif ($headerName === 'code') {
                    $codeCol = $colLetter;
                } elseif ($headerName === 'count') {
                    $countCol = $colLetter;
                } elseif ($headerName === 'description') {
                    $descriptionCol = $colLetter;
                }
            }

            if (!$subdistrictCol) {
                $this->error("Could not find subdistrict code column in sheet!");
            } else {
                $countImported = 0;
                $countSkipped = 0;
                foreach ($rows as $row) {
                    $subdistrictCode = trim($row[$subdistrictCol] ?? '');
                    if (empty($subdistrictCode)) {
                        continue;
                    }

                    // Pad subdistrict code if needed (subdistrict codes are usually 7 digits)
                    if (strlen($subdistrictCode) < 7) {
                        $subdistrictCode = str_pad($subdistrictCode, 7, '0', STR_PAD_LEFT);
                    }

                    $areaId = $subdistrictMap[$subdistrictCode] ?? null;

                    if (!$areaId) {
                        $countSkipped++;
                        continue;
                    }

                    $insertData[] = [
                        'area_id' => $areaId,
                        'area_type' => Subdistrict::class,
                        'category' => null,
                        'code' => isset($codeCol) && isset($row[$codeCol]) ? trim($row[$codeCol]) : null,
                        'description' => isset($descriptionCol) && isset($row[$descriptionCol]) ? trim($row[$descriptionCol]) : null,
                        'count' => isset($countCol) && isset($row[$countCol]) ? (int) $row[$countCol] : 0,
                    ];
                    $countImported++;
                }
                $this->info("Processed subdistricts: {$countImported} rows matched, {$countSkipped} rows skipped (not found in DB).");
            }
        } else {
            $this->warn("Sheet 'subdistrict' not found in Excel file.");
        }

        // 2. Process Village sheet
        $villageSheet = $spreadsheet->getSheetByName('village');
        if ($villageSheet) {
            $this->info("Processing 'village' sheet...");
            $rows = $villageSheet->toArray(null, true, false, true);
            $header = array_shift($rows);

            $villageCol = null;
            $categoryCol = null;
            $countCol = null;
            $descriptionCol = null;

            foreach ($header as $colLetter => $headerName) {
                $headerName = strtolower(trim($headerName));
                if ($headerName === 'village') {
                    $villageCol = $colLetter;
                } elseif ($headerName === 'category') {
                    $categoryCol = $colLetter;
                } elseif ($headerName === 'count') {
                    $countCol = $colLetter;
                } elseif ($headerName === 'description') {
                    $descriptionCol = $colLetter;
                }
            }

            if (!$villageCol) {
                $this->error("Could not find village code column in sheet!");
            } else {
                $countImported = 0;
                $countSkipped = 0;
                foreach ($rows as $row) {
                    $villageCode = trim($row[$villageCol] ?? '');
                    if (empty($villageCode)) {
                        continue;
                    }

                    // Pad village code if needed (village codes are usually 10 digits)
                    if (strlen($villageCode) < 10) {
                        $villageCode = str_pad($villageCode, 10, '0', STR_PAD_LEFT);
                    }

                    $areaId = $villageMap[$villageCode] ?? null;

                    if (!$areaId) {
                        $countSkipped++;
                        continue;
                    }

                    $insertData[] = [
                        'area_id' => $areaId,
                        'area_type' => Village::class,
                        'category' => isset($categoryCol) && isset($row[$categoryCol]) ? trim($row[$categoryCol]) : null,
                        'code' => null,
                        'description' => isset($descriptionCol) && isset($row[$descriptionCol]) ? trim($row[$descriptionCol]) : null,
                        'count' => isset($countCol) && isset($row[$countCol]) ? (int) $row[$countCol] : 0,
                    ];
                    $countImported++;
                }
                $this->info("Processed villages: {$countImported} rows matched, {$countSkipped} rows skipped (not found in DB).");
            }
        } else {
            $this->warn("Sheet 'village' not found in Excel file.");
        }

        if (empty($insertData)) {
            $this->warn("No data to import.");
            return 0;
        }

        $this->info("Truncating kbli_statistics table...");
        DB::table('kbli_statistics')->truncate();

        $this->info("Inserting " . count($insertData) . " records...");
        $chunks = array_chunk($insertData, 500);

        foreach ($chunks as $chunk) {
            DB::table('kbli_statistics')->insert($chunk);
        }

        $this->info("Import completed successfully!");
        return 0;
    }
}

