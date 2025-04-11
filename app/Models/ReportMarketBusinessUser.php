<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReportMarketBusinessUser extends Model
{
    use HasFactory, HasUuids;
    protected $guarded = [];
    public $timestamps = false;
    protected $table = 'report_market_business_user';

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
