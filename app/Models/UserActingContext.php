<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserActingContext extends Model
{
    protected $table = 'user_acting_contexts';

    protected $fillable = [
        'user_id',
        'acting_org_id',
        'acting_role',
        'active',
        'created_by',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
