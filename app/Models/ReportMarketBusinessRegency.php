<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReportMarketBusinessRegency extends Model
{
    use HasFactory, HasUuids;
    protected $guarded = [];
    public $timestamps = true;
    protected $table = 'report_market_business_regency';

    public function organization()
    {
        return $this->belongsTo(Organization::class, 'organization_id');
    }

    public function marketType()
    {
        return $this->belongsTo(MarketType::class, 'market_type_id');
    }
}
