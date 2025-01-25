<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AssignmentStatus extends Model
{
    use HasFactory;
    protected $guarded = [];
    protected $table = 'assignment_status';
    protected $keyType = 'string';
    public $incrementing = false;
}
