<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CategorizedBusiness extends Model
{
    use HasFactory, HasUuids, SoftDeletes;
    protected $guarded = [];
    public $timestamps = false;
    protected $table = 'categorized_business';

    public function regency()
    {
        return $this->belongsTo(Regency::class);
    }

    public function subdistrict()
    {
        return $this->belongsTo(Subdistrict::class);
    }

    public function village()
    {
        return $this->belongsTo(Village::class);
    }

    public function sls()
    {
        return $this->belongsTo(Sls::class);
    }

    public function status()
    {
        return $this->belongsTo(Status::class);
    }

    public function pcl()
    {
        return $this->belongsTo(User::class, 'pcl_id');
    }
}
