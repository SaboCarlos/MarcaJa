<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('perfil', 20)->default('utente')->after('email');   // utente | secretaria | medico
            $table->string('telefone', 15)->nullable()->unique()->after('perfil');
            $table->foreignId('medico_id')->nullable()->after('telefone')->constrained('medicos')->nullOnDelete();
            $table->boolean('letras_grandes')->default(false)->after('medico_id');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('medico_id');
            $table->dropUnique(['telefone']);
            $table->dropColumn(['perfil', 'telefone', 'letras_grandes']);
        });
    }
};
