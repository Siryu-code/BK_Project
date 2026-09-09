<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AbsensiDetail extends Model
{
    use SoftDeletes;

    protected $table = 'absensi_detail';
    protected $fillable = [
        'snapshot_siswa_harian_id',
        'nama',
        'nomor_absen',
        'tipe',
        'keterangan',
        'tanggal_jam_mulai',
        'tanggal_jam_selesai',
    ];

    protected $casts = [
        'tanggal_jam_mulai' => 'datetime',
        'tanggal_jam_selesai' => 'datetime',
    ];

    public function snapshotSiswaHarian()
    {
        return $this->belongsTo(SnapshotSiswaHarian::class);
    }
}
