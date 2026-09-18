<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserSlsCensus extends Model
{
    protected $guarded = [];
    protected $table = 'user_sls_census';
    public $timestamps = false;

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function sls()
    {
        return $this->belongsTo(Sls::class);
    }
}
