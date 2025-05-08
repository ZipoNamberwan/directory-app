<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SupplementUploadStatus extends Model
{
    use HasFactory, HasUuids;
    protected $guarded = [];
    protected $table = 'supplement_upload_status';
    public $incrementing = false;

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

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

    public static function getStatusValues()
    {
        return [
            ['name' => 'start', 'value' => 'start'],
            ['name' => 'loading', 'value' => 'loading'],
            ['name' => 'processing', 'value' => 'processing'],
            ['name' => 'success', 'value' => 'success'],
            ['name' => 'failed', 'value' => 'failed'],
            ['name' => 'success with error', 'value' => 'success with error'],
        ];
    }
}
