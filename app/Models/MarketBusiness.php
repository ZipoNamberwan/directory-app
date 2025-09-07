<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MarketBusiness extends BaseModel
{
    use HasFactory, HasUuids;
    protected $guarded = [];
    protected $table = 'market_business';
    public $incrementing = false;

    protected $casts = [
        'is_locked' => 'boolean',
    ];
    
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function market()
    {
        return $this->belongsTo(Market::class, 'market_id');
    }

    public function regency()
    {
        return $this->belongsTo(Regency::class, 'regency_id');
    }

    public function subdistrict()
    {
        return $this->belongsTo(Subdistrict::class, 'subdistrict_id');
    }

    public function village()
    {
        return $this->belongsTo(Village::class, 'village_id');
    }

    public function sls()
    {
        return $this->belongsTo(Sls::class, 'sls_id');
    }

    public function anomalies()
    {
        return $this->morphMany(AnomalyRepair::class, 'business');
    }
}
