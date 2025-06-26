<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReportSupplementBusinessRegency extends Model
{
    use HasFactory, HasUuids;
    protected $guarded = [];
    public $timestamps = true;
    protected $table = 'report_supplement';

    public function organization()
    {
        return $this->belongsTo(Organization::class, 'organization_id');
    }
}
