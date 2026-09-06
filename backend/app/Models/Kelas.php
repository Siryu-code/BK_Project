<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Kelas extends Model
{
    protected $fillable = [
        'nama_kelas',
        'tingkat',
        'jumlah_laki_laki',
        'jumlah_perempuan',
    ];

    public function snapshotSiswaHarian()
    {
        return $this->hasMany(SnapshotSiswaHarian::class);
    }

    public function tidakNaikLulus()
    {
        return $this->hasMany(TidakNaikLulus::class);
    }

    public function pindah()
    {
        return $this->hasMany(Pindah::class);
    }
}
