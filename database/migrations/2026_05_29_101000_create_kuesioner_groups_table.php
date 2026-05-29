<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kuesioner_groups', function (Blueprint $table) {
            $table->id();
            $table->string('nama_projek');
            $table->json('semua_jawaban');
            $table->enum('status', ['pending', 'processed'])->default('pending');
            $table->string('hasil_akhir_json')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kuesioner_groups');
    }
};