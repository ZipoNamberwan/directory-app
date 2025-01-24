<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExportAssignmentStatus extends Model
{
    use HasFactory;
    protected $guarded = [];
    protected $table = 'export_assignment_status';
}
