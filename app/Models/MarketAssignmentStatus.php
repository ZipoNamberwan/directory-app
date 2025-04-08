<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MarketAssignmentStatus extends Model
{
    use HasFactory;
    protected $guarded = [];
    protected $table = 'market_assignment_status';
    protected $keyType = 'string';
    public $incrementing = false;
}
