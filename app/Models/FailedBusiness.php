<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FailedBusiness extends Model
{
    use HasFactory;
    protected $guarded = [];
    protected $table = 'failed_business';
    public $incrementing = false;
}
