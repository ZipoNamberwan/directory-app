<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReportRegency extends Model
{
    use HasFactory, HasUuids;
    protected $guarded = [];
    public $timestamps = true;
    protected $table = 'report_regency';

    public function regency()
    {
        return $this->belongsTo(Regency::class);
    }
}
