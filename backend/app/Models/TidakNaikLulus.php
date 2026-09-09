<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TidakNaikLulus extends Model
{
    use SoftDeletes;

    protected $table = 'tidak_naik_lulus';
    protected $fillable = [
        'kelas_id',
        'nama',
        'nomor_absen',
        'nis',
        'keterangan',
        'gender',
        'tahun_ajaran',
        'status',
    ];

    public function kelas()
    {
        return $this->belongsTo(Kelas::class);
    }
}
