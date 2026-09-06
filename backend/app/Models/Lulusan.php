<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Lulusan extends Model
{
    protected $fillable = [
        'tahun',
        'total_cowo',
        'total_cewe',
        'total_kerja',
        'total_kuliah',
    ];

    protected $casts = [
        'tahun' => 'integer',
    ];
}
