<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Menambahkan:
     * 1. SoftDeletes (deleted_at) pada tabel users & pesertas — data tidak pernah terhapus permanen
     * 2. Index pada kolom yang sering digunakan sebagai filter di dashboard
     */
    public function up(): void
    {
        // SoftDeletes untuk users
        Schema::table('users', function (Blueprint $table) {
            $table->softDeletes();
        });

        // SoftDeletes + index performa untuk pesertas
        Schema::table('pesertas', function (Blueprint $table) {
            $table->softDeletes();

            // Index untuk query filter dashboard Penyelenggara & stats kuadran
            $table->index('kejuruan', 'idx_pesertas_kejuruan');
            $table->index('diagnosis_awal', 'idx_pesertas_diagnosis');
            $table->index('status_instruktur', 'idx_pesertas_status_instruktur');
            $table->index('status_pengantar_kerja', 'idx_pesertas_status_pengantar');
            $table->index('status_kelulusan', 'idx_pesertas_status_kelulusan');
            $table->index('status_pemberdayaan', 'idx_pesertas_status_pemberdayaan');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('pesertas', function (Blueprint $table) {
            $table->dropSoftDeletes();
            $table->dropIndex('idx_pesertas_kejuruan');
            $table->dropIndex('idx_pesertas_diagnosis');
            $table->dropIndex('idx_pesertas_status_instruktur');
            $table->dropIndex('idx_pesertas_status_pengantar');
            $table->dropIndex('idx_pesertas_status_kelulusan');
            $table->dropIndex('idx_pesertas_status_pemberdayaan');
        });
    }
};
