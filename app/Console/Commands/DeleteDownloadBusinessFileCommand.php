<?php

namespace App\Console\Commands;

use App\Models\AssignmentStatus;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class DeleteDownloadBusinessFileCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:delete-download-business-file';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete download business files older than 1 day';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $statuses = AssignmentStatus::where('created_at', '<', now()->subDay())
            ->where(function ($query) {
                $query->where('type', 'download-supplement-business')
                    ->orWhere('type', 'download-market-raw');
            })
            ->get();

        foreach ($statuses as $status) {
            $filePath = '';
            if ($status->type === 'download-market-raw') {
                $filePath = "market_business_raw/{$status->id}.csv";
            } elseif ($status->type === 'download-supplement-business') {
                $filePath = "supplement/{$status->id}.csv";
            }

            if ($filePath && Storage::delete($filePath)) {
                // Storage::delete() already ignores missing files and returns true if deleted
                $status->file_has_deleted = true;
                $status->save();
            }
        }
    }
}
