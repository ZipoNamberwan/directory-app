<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Market extends Model
{
    use HasFactory, HasUuids;
    protected $guarded = [];
    protected $table = 'markets';
    public $incrementing = false;
    protected $appends = ['transformed_completion_status'];

    public function regency()
    {
        return $this->belongsTo(Regency::class, 'regency_id');
    }

    public function subdistrict()
    {
        return $this->belongsTo(Subdistrict::class, 'subdistrict_id');
    }

    public function village()
    {
        return $this->belongsTo(Village::class, 'village_id');
    }

    public function marketType()
    {
        return $this->belongsTo(MarketType::class, 'market_type_id');
    }

    public function users()
    {
        return $this->belongsToMany(User::class)
            ->withPivot('user_firstname', 'market_name')
            ->withTimestamps();
    }

    public function organization()
    {
        return $this->belongsTo(Organization::class, 'organization_id');
    }

    public function businesses()
    {
        return $this->hasMany(MarketBusiness::class, 'market_id');
    }

    public function getTransformedCompletionStatusAttribute()
    {
        if ($this->completion_status == 'not start') {
            return 'Belum Dimulai';
        } else if ($this->completion_status == 'on going') {
            return 'Sedang Jalan';
        } else if ($this->completion_status == 'done') {
            return 'Selesai';
        }
    }

    public static function getTargetCategoryValues()
    {
        return [
            ['name' => 'Target', 'value' => 'target'],
            ['name' => 'Non Target', 'value' => 'non target'],
        ];
    }

    public static function getCompletionStatusValues()
    {
        return [
            ['name' => 'Belum Dimulai', 'value' => 'not start'],
            ['name' => 'Sedang Jalan', 'value' => 'on going'],
            ['name' => 'Selesai', 'value' => 'done'],
        ];
    }

    public static function getTransformedCompletionStatusByValue($value)
    {
        if ($value == 'not start') {
            return 'Belum Dimulai';
        } else if ($value == 'on going') {
            return 'Sedang Jalan';
        } else if ($value == 'done') {
            return 'Selesai';
        }
        return '';
    }

    public static function getTransformedTargetCategoryByValue($value)
    {
        if ($value == 'target') {
            return 'Target';
        } else if ($value == 'non target') {
            return 'Non Target';
        }
        return '';
    }
}
