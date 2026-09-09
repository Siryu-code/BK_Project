<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
|--------------------------------------------------------------------------
| Snapshot siswa harian
|--------------------------------------------------------------------------
| Jalan tiap hari, pagi-pagi buta - sebelum sekre mulai input absensi
| dan sebelum BK mulai isi jurnal.
*/
Schedule::command('app:generate-snapshot-harian')->dailyAt('00:05');

/*
|--------------------------------------------------------------------------
| NKO (Naik Kelas Otomatis)
|--------------------------------------------------------------------------
| Jalan 1x setahun, akhir Juni. Waktunya (23:50) sengaja diset LEBIH AWAL
| dari jadwal eksekusi pindah di bawah (23:55), supaya di hari yang sama
| NKO selalu selesai duluan sebelum pindah dieksekusi - sesuai aturan
| "kalau tanggal_efektif pindah bentrok sama NKO, NKO duluan".
*/
Schedule::command('app:jalankan-nko')->yearlyOn(6, 30, '23:50');

/*
|--------------------------------------------------------------------------
| Eksekusi pindah terjadwal
|--------------------------------------------------------------------------
| Jalan tiap hari, dijadwal SETELAH waktu NKO (lihat catatan di atas).
*/
Schedule::command('app:eksekusi-pindah-terjadwal')->dailyAt('23:55');
