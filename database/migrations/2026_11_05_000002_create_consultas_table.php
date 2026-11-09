<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('consultas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('medico_id')->constrained('medicos')->cascadeOnDelete();
            $table->string('paciente_nome');
            $table->string('paciente_telefone', 15);
            $table->date('data');
            $table->string('hora', 5);                  // "HH:MM"
            $table->string('motivo')->nullable();
            $table->string('estado')->default('marcada'); // marcada | cancelada
            $table->timestamps();

            $table->index(['medico_id', 'data', 'hora']);
            $table->index('paciente_telefone');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('consultas');
    }
};
