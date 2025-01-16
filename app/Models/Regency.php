<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Regency extends Model
{
    use HasFactory;
    protected $guarded = [];
    public $timestamps = false;
    protected $table = 'regencies';

    public function subdistricts()
    {
        return $this->hasMany(Subdistrict::class, 'regency_id');
    }
}
