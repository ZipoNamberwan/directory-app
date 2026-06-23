<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Info extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $guarded = [];
    protected $table = 'infos';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $casts = [
        'is_published' => 'boolean',
        'published_at' => 'datetime',
    ];

    const TYPES = [
        'announcement'     => 'Pengumuman',
        'faq'              => 'FAQ',
        'problem-solution' => 'Kendala/Solusi',
        'other'            => 'Lainnya',
    ];
}
