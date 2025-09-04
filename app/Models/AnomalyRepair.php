<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AnomalyRepair extends Model
{
    use HasFactory, HasUuids;
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

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
