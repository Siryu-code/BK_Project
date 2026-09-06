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
        Schema::create('tidak_naik_lulus', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kelas_id')->constrained('kelas')->restrictOnDelete();
            $table->string('nama');
            $table->string('nomor_absen');
            $table->string('nis');
            $table->string('keterangan')->nullable();
            $table->enum('gender', ['cowok', 'cewek']);
            $table->smallInteger('tahun_ajaran');
            $table->enum('status', ['belum', 'sudah'])->default('belum');
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tidak_naik_luluses');
    }
};
