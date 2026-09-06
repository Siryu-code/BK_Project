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
        Schema::create('jurnal_bk', function (Blueprint $table) {
            $table->id();
            $table->foreignId('snapshot_siswa_harian_id')->constrained('snapshot_siswa_harian')->restrictOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->tinyInteger('jam_ke');
            $table->text('tema');
            $table->text('kasus');
            $table->softDeletes();
            $table->timestamps();

            $table->unique(['snapshot_siswa_harian_id', 'jam_ke']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('jurnal_bks');
    }
};
