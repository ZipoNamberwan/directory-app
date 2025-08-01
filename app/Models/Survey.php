<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Survey extends Model
{
    use HasUuids;
    use HasFactory;

    protected $guarded = [];
    protected $table = 'surveys';
    public $incrementing = false;
    public $timestamps = false;
}
