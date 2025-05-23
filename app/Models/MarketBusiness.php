<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MarketBusiness extends Model
{
    use HasFactory, HasUuids;
    protected $guarded = [];
    protected $table = 'market_business';
    public $incrementing = false;

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
}
