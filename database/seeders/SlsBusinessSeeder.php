<?php

namespace Database\Seeders;

use App\Jobs\SlsBusinessJob;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

class SlsBusinessSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $folderPath = storage_path('../python_script/result/usaha'); // Adjust this path to your folder

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

                        SlsBusinessJob::dispatch($csvData);
                    }
                } catch (\Exception $e) {
                    dd($e);
                }
            }
        }
    }
}
