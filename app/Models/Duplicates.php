<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Duplicates extends Model
{
    use HasUuids;
    use HasFactory;

    protected $guarded = [];
    protected $table = 'duplicate_candidate';
    public $incrementing = true;
    public $timestamps = false;
}
