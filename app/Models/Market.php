<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Market extends Model
{
    use HasFactory;
    protected $guarded = [];
    public $timestamps = false;
    protected $table = 'markets';
    public $incrementing = false;

    public function regency()
    {
        return $this->belongsTo(Regency::class, 'regency_id');
    }

    public function users()
    {
        return $this->belongsToMany(User::class)
            ->withPivot('user_firstname', 'market_name')
            ->withTimestamps();
    }
}
