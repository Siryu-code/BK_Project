<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Kelas;

class KelasController extends Controller
{
    /**
     * Total siswa - 3 level, semua dihitung on the fly
     * dari 12 baris tabel `kelas`.
     */
    public function index()
    {
        $kelas = Kelas::orderBy('tingkat')->orderBy('nama_kelas')->get();

        // Level 1: perkelas (data mentah, 12 baris)
        $perKelas = $kelas->map(function ($k) {
            return [
                'kelas_id'   => $k->id,
                'nama_kelas' => $k->nama_kelas,
                'tingkat'    => $k->tingkat,
                'laki_laki'  => $k->jumlah_laki_laki,
                'perempuan'  => $k->jumlah_perempuan,
                'total'      => $k->jumlah_laki_laki + $k->jumlah_perempuan,
            ];
        });

        // Level 2: perangkatan (gabung 4 TKJ per tingkat, jadi 3 baris)
        $perAngkatan = $kelas->groupBy('tingkat')->map(function ($group, $tingkat) {
            return [
                'tingkat'   => (int) $tingkat,
                'laki_laki' => $group->sum('jumlah_laki_laki'),
                'perempuan' => $group->sum('jumlah_perempuan'),
                'total'     => $group->sum('jumlah_laki_laki') + $group->sum('jumlah_perempuan'),
            ];
        })->values();

        // Level 3: pertahun (gabung semua 12 baris jadi 1)
        $totalLaki = $kelas->sum('jumlah_laki_laki');
        $totalPerempuan = $kelas->sum('jumlah_perempuan');

        $perTahun = [
            'laki_laki' => $totalLaki,
            'perempuan' => $totalPerempuan,
            'total'     => $totalLaki + $totalPerempuan,
        ];

        return response()->json([
            'per_kelas'    => $perKelas,
            'per_angkatan' => $perAngkatan,
            'per_tahun'    => $perTahun,
        ]);
    }

    /**
     * Daftar kelas ringan buat halaman select class sisi sekre
     * (gak perlu ikutan hitung total siswa, cukup buat dropdown/list).
     */
    public function selectClass()
    {
        $kelas = Kelas::select('id', 'nama_kelas', 'tingkat')
            ->orderBy('tingkat')
            ->orderBy('nama_kelas')
            ->get();

        return response()->json($kelas);
    }
}
