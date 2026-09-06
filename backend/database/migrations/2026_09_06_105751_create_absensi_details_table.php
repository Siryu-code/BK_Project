<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('absensi_detail', function (Blueprint $table) {
            $table->id();
            $table->foreignId('snapshot_siswa_harian_id')->constrained('snapshot_siswa_harian')->restrictOnDelete();
            $table->string('nama');
            $table->string('nomor_absen');
            $table->enum('tipe', ['sakit', 'alpa', 'izin', 'terlambat', 'dispen']);
            $table->text('keterangan')->nullable();
            $table->datetime('tanggal_jam_mulai');
            $table->datetime('tanggal_jam_selesai');
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('absensi_details');
    }
};
