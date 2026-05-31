<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KbliStatistic extends Model
{
    public $timestamps = false;

    protected $guarded = [];
    public function area()
    {
        return $this->morphTo();
    }
}
