<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VersionLeresPak extends Model
{
    use HasUuids;
    use HasFactory;
    protected $guarded = [];
    protected $table = 'versions_leres_pak';
}
