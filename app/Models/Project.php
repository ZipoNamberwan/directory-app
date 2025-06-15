<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Project extends Model
{
    use HasUuids;
    use HasFactory;
    use SoftDeletes;

    protected $guarded = [];
    protected $table = 'projects';

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
