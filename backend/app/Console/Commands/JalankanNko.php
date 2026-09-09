<?php

namespace App\Console\Commands;

use App\Models\Kelas;
use App\Models\Lulusan;
use App\Models\TidakNaikLulus;
use App\Models\Pindah;
use App\Models\NkoLog;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class JalankanNko extends Command
{
    protected $signature = 'app:jalankan-nko';
    protected $description = 'Jalankan siklus Naik Kelas Otomatis: freeze lulusan, geser data kelas, flip status tidak naik/lulus, purge recycle bin';

    public function handle()
    {
        DB::transaction(function () {
            $this->freezeLulusan();
            $this->geserDataKelas();
            $this->flipStatusTidakNaikLulus();
            $this->purgeRecycleBin();
            $this->catatLog();
        });

        $this->info('NKO berhasil dijalankan pada ' . Carbon::now());
    }

    /**
     * NOTE buat Kai: freeze lulusan aku taruh sebagai LANGKAH PERTAMA
     * di dalam proses NKO ini, dieksekusi tepat sebelum geser data kelas.
     * Ini karena dari diskusi kemarin masih ada 2 kemungkinan makna
     * "difoto Mei/sebelum NKO" - beneran command terpisah di bulan Mei,
     * atau cuma "sebelum langkah geser data (dalam proses NKO yang sama)".
     * Aku pilih interpretasi kedua karena lebih simpel (gak nambah 1
     * command lagi). Konfirmasi ke aku kalau maksudmu yang pertama -
     * gampang dipecah jadi command `FreezeLulusan` sendiri kalau perlu.
     */
    private function freezeLulusan(): void
    {
        $kelas12 = Kelas::where('tingkat', 12)->get();

        Lulusan::create([
            'tahun'        => Carbon::now()->year,
            'total_cowo'   => $kelas12->sum('jumlah_laki_laki'),
            'total_cewe'   => $kelas12->sum('jumlah_perempuan'),
            'total_kerja'  => null,
            'total_kuliah' => null,
        ]);
    }

    /**
     * Geser data mentah per TKJ: 11 -> 12, 10 -> 11, lalu 10 direset ke 0.
     * Data 10/11/12 diambil dulu semua ke variabel PHP sebelum ada yang
     * ditulis, jadi gak ada risiko kebaca nilai yang udah ketimpa.
     */
    private function geserDataKelas(): void
    {
        foreach (['TKJ 1', 'TKJ 2', 'TKJ 3', 'TKJ 4'] as $nama) {
            $kelas10 = Kelas::where('nama_kelas', $nama)->where('tingkat', 10)->first();
            $kelas11 = Kelas::where('nama_kelas', $nama)->where('tingkat', 11)->first();
            $kelas12 = Kelas::where('nama_kelas', $nama)->where('tingkat', 12)->first();

            $kelas12->update([
                'jumlah_laki_laki' => $kelas11->jumlah_laki_laki,
                'jumlah_perempuan' => $kelas11->jumlah_perempuan,
            ]);

            $kelas11->update([
                'jumlah_laki_laki' => $kelas10->jumlah_laki_laki,
                'jumlah_perempuan' => $kelas10->jumlah_perempuan,
            ]);

            $kelas10->update([
                'jumlah_laki_laki' => 0,
                'jumlah_perempuan' => 0,
            ]);
        }
    }

    private function flipStatusTidakNaikLulus(): void
    {
        TidakNaikLulus::where('status', 'belum')->update(['status' => 'sudah']);
    }

    private function purgeRecycleBin(): void
    {
        TidakNaikLulus::onlyTrashed()->forceDelete();
        Pindah::onlyTrashed()->forceDelete();
    }

    private function catatLog(): void
    {
        NkoLog::create([
            'tanggal_eksekusi' => Carbon::now(),
        ]);
    }
}
