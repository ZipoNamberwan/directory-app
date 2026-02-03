<?php

namespace Database\Seeders;

use App\Models\Regency;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use App\Jobs\SlsJob;
use App\Jobs\VillageJob;
use App\Jobs\SubdistrictJob;
use App\Models\Status;

class AreaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $types = ['sls'];

        foreach ($types as $type) {

            $folderPath = storage_path('../python_script/result/' . $type); // Adjust this path to your folder

            // Get all files in the folder
            $files = File::files($folderPath);

            foreach ($files as $file) {
                // Process only CSV files
                if ($file->getExtension() === 'csv') {
                    try {
                        if (($handle = fopen($file, 'r')) !== false) {
                            $header = null;
                            $csvData = [];

                            // Loop through each row
                            while (($row = fgetcsv($handle, 1000, ',')) !== false) {
                                // If this is the first row, use it as the header
                                if (!$header) {
                                    $header = $row; // Store header names
                                } else {
                                    // Combine the header with the row values
                                    $csvData[] = array_combine($header, $row);
                                }
                            }

                            // Close the file
                            fclose($handle);

                            if ($type == 'kec') {
                                SubdistrictJob::dispatch($csvData);
                            } else if ($type == 'des') {
                                VillageJob::dispatch($csvData);
                            } else if ($type == 'sls') {
                                SlsJob::dispatch($csvData);
                            }
                        }
                    } catch (\Exception $e) {
                        dd('error');
                    }
                }
            }
        }
    }
}
