<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DuplicateCandidate extends Model
{
    use HasFactory, HasUuids;
    protected $guarded = [];
    protected $table = 'duplicate_candidates';

    public function centerBusiness()
    {
        return $this->morphTo();
    }

    public function nearbyBusiness()
    {
        return $this->morphTo();
    }

    public function lastConfirmedBy()
    {
        return $this->belongsTo(User::class, 'last_confirmed_by');
    }
}
