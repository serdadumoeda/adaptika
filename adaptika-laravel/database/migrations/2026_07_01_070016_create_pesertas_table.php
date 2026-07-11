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
        Schema::create('pesertas', function (Blueprint $table) {
            $table->id();
            $table->string('nama');
            $table->string('kejuruan');
            $table->integer('skor_logika_numerik')->default(0);
            $table->integer('skor_spasial_figural')->default(0);
            $table->string('kode_riasec')->nullable();
            $table->string('profil_riasec')->nullable();
            $table->json('detail_siaplatih')->nullable();
            $table->string('diagnosis_awal')->nullable();
            
            // Status Tracking
            $table->string('status_kelulusan')->default('Belum Dievaluasi');
            $table->string('status_instruktur')->default('Belum Ditangani');
            $table->text('catatan_instruktur')->nullable();
            $table->string('status_pengantar_kerja')->default('Belum Ditangani');
            $table->text('catatan_pengantar_kerja')->nullable();
            $table->string('status_pemberdayaan')->default('Belum Disalurkan');
            $table->text('catatan_pemberdayaan')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pesertas');
    }
};
