<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MarketType extends Model
{
    use HasFactory;
    protected $guarded = [];
    protected $table = 'market_types';
    public $timestamps = false;
}
