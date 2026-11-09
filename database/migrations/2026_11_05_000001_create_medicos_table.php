<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('medicos', function (Blueprint $table) {
            $table->id();
            $table->string('nome');
            $table->string('especialidade');
            $table->string('dias');            // ex.: "Seg – Sex"
            $table->string('sala')->nullable(); // ex.: "Sala 2"
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('medicos');
    }
};
