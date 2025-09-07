<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AnomalyRepair extends Model
{
    use HasFactory, HasUuids, SoftDeletes;
    protected $guarded = [];
    protected $table = 'anomaly_repairs';
    public $incrementing = false;

    public function anomalyType()
    {
        return $this->belongsTo(AnomalyType::class);
    }

    public function business()
    {
        return $this->morphTo();
    }

    public function lastRepairedBy()
    {
        return $this->belongsTo(User::class, 'last_repaired_by');
    }
}
