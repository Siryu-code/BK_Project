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
        Schema::create('lulusan', function (Blueprint $table) {
            $table->id();
            $table->year('tahun');
            $table->integer('total_cowo');
            $table->integer('total_cewe');
            $table->integer('total_kerja')->nullable();
            $table->integer('total_kuliah')->nullable();
            $table->timestamps();

            $table->unique('tahun');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lulusans');
    }
};
