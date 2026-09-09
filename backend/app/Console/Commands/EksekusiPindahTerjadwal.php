<?php

namespace App\Console\Commands;

use App\Models\Pindah;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class EksekusiPindahTerjadwal extends Command
{
    protected $signature = 'app:eksekusi-pindah-terjadwal';
    protected $description = 'Eksekusi data pindah (in/out) yang tanggal_efektif-nya sudah tiba, lalu update angka di tabel kelas';

    public function handle()
    {
        $tanggalHariIni = Carbon::today()->toDateString();

        // <= hari ini (bukan cuma "=") sebagai jaring pengaman: kalau job
        // ini sempat gak jalan beberapa hari (server down dsb), data yang
        // ketinggalan tetap ke-catch up, gak hilang begitu aja.
        $daftarPindah = Pindah::where('status', 'belum')
            ->where('tanggal_efektif', '<=', $tanggalHariIni)
            ->get();

        $jumlahDieksekusi = 0;

        foreach ($daftarPindah as $pindah) {
            DB::transaction(function () use ($pindah) {
                $kelas = $pindah->kelas; // relasi belongsTo ke Kelas
                $kolom = $pindah->gender === 'cowok' ? 'jumlah_laki_laki' : 'jumlah_perempuan';

                if ($pindah->tipe === 'in') {
                    $kelas->increment($kolom);
                } else { // out
                    $kelas->decrement($kolom);
                }

                $pindah->status = 'sudah';
                $pindah->save();
            });

            $jumlahDieksekusi++;
        }

        $this->info("{$jumlahDieksekusi} data pindah berhasil dieksekusi (tanggal_efektif <= {$tanggalHariIni}).");
    }
}
