<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AssignmentStatus extends Model
{
    use HasFactory;
    protected $guarded = [];
    protected $table = 'assignment_status';
    protected $keyType = 'string';
    public $incrementing = false;

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public static function getTransformedTypeByValue($value)
    {
        if ($value == '1') {
            return 'download-market-master';
        } else if ($value == '2') {
            return 'download-supplement-business';
        } else if ($value == '3') {
            return 'download-market-raw';
        } else if ($value == '4') {
            return 'upload-market-assignment';
        } else if ($value == '5') {
            return ['dashboard-regency', 'dashboard-user', 'dashboard-market', 'dashboard-supplement', 'dashboard-area'];
        } else if ($value == '6') {
            return 'download-anomaly';
        } else if ($value == '7') {
            return 'download-duplicate';
        } else {
            return null;
        }
    }

    public static function getFolderDownloadAndTypeByValue($value)
    {
        if ($value == '1') {
            // 'download-market-master';
            return ['name' => 'market_business_master', 'extension' => '.csv'];
        } else if ($value == '2') {
            // 'download-supplement-business';
            return ['name' => 'supplement', 'extension' => '.csv'];
        } else if ($value == '3') {
            // 'download-market-raw';
            return ['name' => 'market_business_raw', 'extension' => '.csv'];
        } else if ($value == '4') {
            // 'upload-market-assignment';
            return ['name' => 'upload_market_assignment', 'extension' => '.xlsx'];
        } else if ($value == '5') {
            // ['dashboard-regency', 'dashboard-user', 'dashboard-market', 'dashboard-supplement', 'dashboard-area'];
            return ['name' => 'dashboard_report', 'extension' => '.csv'];
        } else if ($value == '6') {
            // 'download-anomaly';
            return ['name' => 'anomaly', 'extension' => '.csv'];
        } else if ($value == '7') {
            // 'download-duplicate';
            return ['name' => 'duplicate', 'extension' => '.csv'];
        } else {
            return null;
        }
    }
}
