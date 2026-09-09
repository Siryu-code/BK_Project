<?php

namespace App\Console\Commands;

use App\Models\Kelas;
use App\Models\SnapshotSiswaHarian;
use Illuminate\Console\Command;
use Carbon\Carbon;

class GenerateSnapshotHarian extends Command
{
    protected $signature = 'app:generate-snapshot-harian';
    protected $description = 'Generate 12 baris snapshot total siswa harian (per kelas) untuk hari ini';

    public function handle()
    {
        $tanggal = Carbon::today()->toDateString();
        $kelasSemua = Kelas::all();
        $dibuat = 0;

        foreach ($kelasSemua as $kelas) {
            // Idempotent: kalau job kepanggil 2x (misal manual testing +
            // scheduler jalan bareng), gak bikin duplikat berkat cek ini
            // + unique constraint (kelas_id, tanggal) di DB sebagai jaring pengaman kedua.
            $sudahAda = SnapshotSiswaHarian::where('kelas_id', $kelas->id)
                ->where('tanggal', $tanggal)
                ->exists();

            if ($sudahAda) {
                continue;
            }

            SnapshotSiswaHarian::create([
                'kelas_id'            => $kelas->id,
                'tanggal'             => $tanggal,
                'total_siswa_harian'  => $kelas->jumlah_laki_laki + $kelas->jumlah_perempuan,
            ]);

            $dibuat++;
        }

        $this->info("Snapshot tanggal {$tanggal}: {$dibuat} baris baru dibuat, " . ($kelasSemua->count() - $dibuat) . ' sudah ada sebelumnya.');
    }
}
