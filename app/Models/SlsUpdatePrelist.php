<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SlsUpdatePrelist extends Model
{
    use HasFactory, HasUuids;
    protected $guarded = [];
    public $timestamps = false;
    protected $table = 'sls_update_prelist';

    public function sls()
    {
        return $this->belongsTo(Sls::class, 'sls_id', 'id');
    }
}
