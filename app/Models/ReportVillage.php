<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReportVillage extends Model
{
    use HasFactory, HasUuids;
    protected $guarded = [];
    public $timestamps = true;
    protected $table = 'report_village';

    public function village()
    {
        return $this->belongsTo(Village::class);
    }
}
