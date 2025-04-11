<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReportMarketBusinessRegency extends Model
{
    use HasFactory, HasUuids;
    protected $guarded = [];
    public $timestamps = false;
    protected $table = 'report_market_business_regency';

    public function regency()
    {
        return $this->belongsTo(Regency::class, 'regency_id');
    }
}
