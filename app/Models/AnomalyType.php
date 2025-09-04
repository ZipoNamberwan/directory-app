<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AnomalyType extends Model
{
    use HasFactory;
    protected $guarded = [];
    protected $table = 'anomaly_types';

    public function repairs()
    {
        return $this->hasMany(AnomalyRepair::class, 'anomaly_type_id');
    }
}
