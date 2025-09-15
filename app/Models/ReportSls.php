<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReportSls extends Model
{
    use HasFactory, HasUuids;
    protected $guarded = [];
    public $timestamps = true;
    protected $table = 'report_sls';

    public function sls()
    {
        return $this->belongsTo(Sls::class);
    }
}
