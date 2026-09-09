<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SnapshotSiswaHarian extends Model
{
    protected $table = 'snapshot_siswa_harian';
    protected $fillable = [
        'kelas_id',
        'tanggal',
        'total_siswa_harian',
    ];

    protected $casts = [
        'tanggal' => 'date',
    ];

    public function kelas()
    {
        return $this->belongsTo(Kelas::class);
    }

    public function jurnalBk()
    {
        return $this->hasMany(JurnalBk::class);
    }

    public function absensiDetail()
    {
        return $this->hasMany(AbsensiDetail::class);
    }
}
