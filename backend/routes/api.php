<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\KelasController;
use App\Http\Controllers\Api\JurnalBkController;
use App\Http\Controllers\Api\AbsensiDetailController;
use App\Http\Controllers\Api\TidakNaikLulusController;
use App\Http\Controllers\Api\LulusanController;
use App\Http\Controllers\Api\PindahController;

/*
|--------------------------------------------------------------------------
| AUTH (BK)
|--------------------------------------------------------------------------
| Login pakai kode_akses aja (bukan Auth::attempt() standar Laravel,
| karena bukan pola identifier+password). Logic-nya custom di controller:
| cari user via kode_akses, cocokkan hash, baru Auth::login() manual.
| Setelah login, keluarin token (Sanctum) buat dipakai request selanjutnya.
*/

Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {

    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']); // identitas BK yang lagi login (Bu Riau / Pak Adi)

    /*
    |----------------------------------------------------------------
    | JURNAL BK
    |----------------------------------------------------------------
    | Bebas create/edit/delete. Controller yang urus resolve
    | kelas+tanggal -> snapshot_siswa_harian_id di baliknya.
    */
    Route::apiResource('jurnal', JurnalBkController::class);

    /*
    |----------------------------------------------------------------
    | PETA SISWA
    |----------------------------------------------------------------
    */
    // total siswa (level perkelas/perangkatan/pertahun dihitung on the fly di controller)
    Route::get('/kelas', [KelasController::class, 'index']);

    // siswa tidak naik / tidak lulus
    Route::apiResource('tidak-naik-lulus', TidakNaikLulusController::class);
    Route::get('/tidak-naik-lulus-recycle-bin', [TidakNaikLulusController::class, 'recycleBin']);
    Route::post('/tidak-naik-lulus/{id}/restore', [TidakNaikLulusController::class, 'restore']);
    Route::delete('/tidak-naik-lulus/{id}/force', [TidakNaikLulusController::class, 'forceDelete']);

    // siswa lulus (cuma bisa index & update kolom kerja/kuliah, gak ada delete/create manual)
    Route::get('/lulusan', [LulusanController::class, 'index']);
    Route::put('/lulusan/{id}', [LulusanController::class, 'update']);

    // pindah (in/out)
    Route::apiResource('pindah', PindahController::class);
    Route::get('/pindah-recycle-bin', [PindahController::class, 'recycleBin']);
    Route::post('/pindah/{id}/restore', [PindahController::class, 'restore']);
    Route::delete('/pindah/{id}/force', [PindahController::class, 'forceDelete']);

    /*
    |----------------------------------------------------------------
    | ABSENSI (sisi BK - bebas lihat/create/edit/delete kapan saja)
    |----------------------------------------------------------------
    */
    Route::apiResource('absensi', AbsensiDetailController::class);
    Route::get('/absensi-kalender', [AbsensiDetailController::class, 'kalender']); // buat tampilan kalender + filter kelas/tahun ajaran

});

/*
|--------------------------------------------------------------------------
| ABSENSI (sisi SEKRE - tanpa akun, cuma modul ini yang bisa diakses)
|--------------------------------------------------------------------------
| Gak pakai middleware auth:sanctum karena sekre memang gak punya akun.
| Batasan "cuma hari ini" & "harus login/kedeteksi sesi" ditegakkan
| di dalam controller, bukan lewat middleware auth biasa.
*/
Route::prefix('sekre')->middleware('web')->group(function () {
    Route::get('/kelas', [KelasController::class, 'selectClass']); // buat halaman select class
    Route::post('/masuk-kelas/{kelasId}', [AbsensiDetailController::class, 'masukKelas']); // kunci sesi ke 1 kelas
    Route::get('/absensi-hari-ini/{kelasId}', [AbsensiDetailController::class, 'todayByKelas']);
    Route::post('/absensi', [AbsensiDetailController::class, 'storeSekre']);
    Route::put('/absensi/{id}', [AbsensiDetailController::class, 'updateToday']);
    Route::delete('/absensi/{id}', [AbsensiDetailController::class, 'destroyToday']);
});
