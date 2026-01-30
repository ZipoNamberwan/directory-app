<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class Sls extends Model
{
    use HasFactory, HasUuids;
    protected $guarded = [];
    public $timestamps = false;
    protected $table = 'sls';

    public function village()
    {
        return $this->belongsTo(Village::class, 'village_id');
    }
    public function updatePrelist()
    {
        return $this->hasOne(SlsUpdatePrelist::class, 'sls_id', 'id');
    }

    protected static function booted()
    {
        static::addGlobalScope('activePeriod', function (Builder $builder) {
            $builder->whereHas('period', function ($q) {
                $q->where('is_active', true);
            });
        });
    }

    public function period()
    {
        return $this->belongsTo(AreaPeriod::class, 'area_period_id');
    }
}
