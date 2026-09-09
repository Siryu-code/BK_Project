<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Pindah extends Model
{
    use SoftDeletes;

    protected $table = 'pindah';
    protected $fillable = [
        'nama',
        'nomor_absen',
        'nis',
        'nisn',
        'kelas_id',
        'sekolah_luar',
        'tipe',
        'status',
        'gender',
        'tanggal_efektif',
    ];

    protected $casts = [
        'tanggal_efektif' => 'date',
    ];

    public function kelas()
    {
        return $this->belongsTo(Kelas::class);
    }
}
