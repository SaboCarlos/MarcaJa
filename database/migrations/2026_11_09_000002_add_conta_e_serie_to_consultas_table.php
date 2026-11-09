<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('consultas', function (Blueprint $table) {
            // Conta do utente a quem a consulta pertence (pode ficar vazia se a secretaria marcar para alguém sem conta)
            $table->foreignId('user_id')->nullable()->after('medico_id')->constrained('users')->nullOnDelete();
            // Quando o utente confirmou que vai à consulta
            $table->timestamp('confirmada_em')->nullable()->after('estado');
            // Consultas mensais da mesma marcação partilham a mesma série
            $table->uuid('serie')->nullable()->index()->after('confirmada_em');
        });
    }

    public function down(): void
    {
        Schema::table('consultas', function (Blueprint $table) {
            $table->dropConstrainedForeignId('user_id');
            $table->dropIndex(['serie']);
            $table->dropColumn(['confirmada_em', 'serie']);
        });
    }
};
