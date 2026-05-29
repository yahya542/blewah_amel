<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kuesioners', function (Blueprint $table) {
            $table->id();
            $table->string('nama_responden');
            $table->integer('usia');
            $table->enum('status', ['pending', 'processed'])->default('pending');
            $table->json('hasil_json')->nullable();
            $table->timestamps();
        });

        Schema::create('kuesioner_comparisons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kuesioner_id')->constrained()->onDelete('cascade');
            $table->string('kriteria_from');
            $table->string('kriteria_to');
            $table->double('value');
            $table->timestamps();
        });

        Schema::create('kuesioner_scores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kuesioner_id')->constrained()->onDelete('cascade');
            $table->string('varietas');
            $table->string('kriteria');
            $table->double('score');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kuesioner_scores');
        Schema::dropIfExists('kuesioner_comparisons');
        Schema::dropIfExists('kuesioners');
    }
};