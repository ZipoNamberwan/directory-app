<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SbrBusiness extends Model
{
    use HasFactory, HasUuids, SoftDeletes;
    protected $guarded = [];
    protected $table = 'sbr_business';
    public $incrementing = false;

    protected $hidden = [
        'coordinate',
    ];
}
