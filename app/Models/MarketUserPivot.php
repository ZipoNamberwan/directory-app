<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MarketUserPivot extends Model
{
    use HasFactory;
    protected $guarded = [];
    protected $table = 'market_user';

    public function user()
    {
        return $this->belongsTo(User::class);
    }
    
    public function market()
    {
        return $this->belongsTo(Market::class);
    }
}
