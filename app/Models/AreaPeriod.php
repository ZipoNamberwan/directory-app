<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class AreaPeriod extends Model
{
    use HasFactory, HasUuids;
    protected $table = 'area_periods';
    public $incrementing = false;
    protected $guarded = [];
}
