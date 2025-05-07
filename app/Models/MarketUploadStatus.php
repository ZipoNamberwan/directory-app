<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MarketUploadStatus extends Model
{
    use HasFactory;
    protected $guarded = [];
    protected $table = 'market_upload_status';
    public $incrementing = false;

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function market()
    {
        return $this->belongsTo(Market::class, 'market_id');
    }

    public static function getStatusValues()
    {
        return [
            ['name' => 'start', 'value' => 'start'],
            ['name' => 'loading', 'value' => 'loading'],
            ['name' => 'processing', 'value' => 'processing'],
            ['name' => 'success', 'value' => 'success'],
            ['name' => 'failed', 'value' => 'failed'],
            ['name' => 'success with error', 'value' => 'success with error'],
        ];
    }
}
