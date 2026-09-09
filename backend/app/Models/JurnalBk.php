<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class JurnalBk extends Model
{
    use SoftDeletes;

    protected $table = 'jurnal_bk';
    protected $fillable = [
        'snapshot_siswa_harian_id',
        'user_id',
        'jam_ke',
        'tema',
        'kasus',
    ];

    public function snapshotSiswaHarian()
    {
        return $this->belongsTo(SnapshotSiswaHarian::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
