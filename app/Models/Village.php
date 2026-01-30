<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class Village extends Model
{
    use HasFactory, HasUuids;
    protected $guarded = [];
    public $timestamps = false;

    public function subdistrict()
    {
        return $this->belongsTo(Subdistrict::class, 'subdistrict_id');
    }

    public function sls()
    {
        return $this->hasMany(Sls::class, 'village_id');
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
