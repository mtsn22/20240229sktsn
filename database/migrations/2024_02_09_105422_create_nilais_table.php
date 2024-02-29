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
        Schema::create('nilais', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('jenis_soal_id')->nullable();
            $table->unsignedBigInteger('mahad_id')->nullable();
            $table->unsignedBigInteger('qism_id')->nullable();
            $table->unsignedBigInteger('qism_detail_id')->nullable();
            $table->unsignedBigInteger('tahun_ajaran_id')->nullable();
            $table->unsignedBigInteger('semester_id')->nullable();
            $table->unsignedBigInteger('kelas_id')->nullable();
            $table->string('kelas_internal')->nullable();
            $table->unsignedBigInteger('mapel_id')->nullable();
            $table->unsignedBigInteger('kategori_soal_id');
            $table->unsignedBigInteger('pengajar_id')->nullable();
            $table->unsignedBigInteger('staff_admin_id')->nullable();
            $table->string('kode_soal')->nullable();
            $table->string('soal_dari_ustadz')->nullable();
            $table->string('status_soal')->nullable();
            $table->string('soal_siap_print')->nullable();
            $table->string('jumlah_print')->nullable();
            $table->string('status_print')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('nilais');
    }
};
