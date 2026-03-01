<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class Subdistrict extends Model
{
    use HasFactory, HasUuids;
    protected $guarded = [];
    public $timestamps = false;
    protected $hidden = [
        'geom',
    ];

    public function regency()
    {
        return $this->belongsTo(Regency::class, 'regency_id');
    }

    public function villages()
    {
        return $this->hasMany(Village::class, 'subdistrict_id');
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
