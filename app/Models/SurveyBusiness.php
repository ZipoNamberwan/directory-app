<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SurveyBusiness extends Model
{
    use HasFactory, HasUuids, SoftDeletes;
    protected $guarded = [];
    protected $table = 'survey_business';
    public $incrementing = false;

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
    public function sls()
    {
        return $this->belongsTo(Sls::class, 'sls_id');
    }

    public function organization()
    {
        return $this->belongsTo(Organization::class, 'organization_id');
    }

    public function survey()
    {
        return $this->belongsTo(Survey::class, 'survey_id');
    }
}
