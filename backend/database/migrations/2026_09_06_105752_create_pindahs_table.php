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
        Schema::create('pindah', function (Blueprint $table) {
            $table->id();
            $table->string('nama');
            $table->string('nomor_absen')->nullable();
            $table->string('nis')->nullable();
            $table->string('nisn')->nullable();
            $table->foreignId('kelas_id')->constrained('kelas')->restrictOnDelete();
            $table->string('sekolah_luar')->nullable();
            $table->enum('tipe', ['in', 'out']);
            $table->enum('status', ['belum', 'sudah'])->default('belum');
            $table->enum('gender', ['cowok', 'cewek']);
            $table->date('tanggal_efektif');
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pindahs');
    }
};
