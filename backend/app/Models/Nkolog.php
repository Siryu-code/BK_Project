<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NkoLog extends Model
{
    protected $fillable = [
        'tanggal_eksekusi',
    ];

    protected $casts = [
        'tanggal_eksekusi' => 'datetime',
    ];
}
