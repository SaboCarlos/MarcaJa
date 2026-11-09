<?php

use App\Http\Controllers\ConsultaController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

// Login, registo e logout (laravel/ui). Sem recuperação de password por e-mail.
Auth::routes(['reset' => false, 'confirm' => false, 'verify' => false]);

// Tela 1 — Início (pública: lista de médicos e especialidades)
Route::get('/', [ConsultaController::class, 'inicio'])->name('inicio');

// Letras grandes (funciona com ou sem sessão iniciada)
Route::post('/letras', [ConsultaController::class, 'letras'])->name('letras');

Route::middleware('auth')->group(function () {
    Route::redirect('/home', '/consultas')->name('home');

    // Tela 2 — Marcar consulta (utente para si; secretaria para quem liga)
    Route::middleware('perfil:utente,secretaria')->group(function () {
        Route::get('/marcar', [ConsultaController::class, 'marcar'])->name('marcar');
        Route::post('/consultas', [ConsultaController::class, 'guardar'])->name('consultas.guardar');
        Route::patch('/consultas/{consulta}/confirmar', [ConsultaController::class, 'confirmar'])->name('consultas.confirmar');
    });

    // Tela 3 — Consultas: cada perfil vê as suas
    Route::get('/consultas', [ConsultaController::class, 'consultas'])->name('consultas');
    Route::patch('/consultas/{consulta}/cancelar', [ConsultaController::class, 'cancelar'])->name('consultas.cancelar');
    Route::patch('/consultas/{consulta}/concluir', [ConsultaController::class, 'concluir'])
        ->middleware('perfil:medico,secretaria')->name('consultas.concluir');

    // Apoio (JSON): horários já ocupados de um médico numa data
    Route::get('/horarios-ocupados', [ConsultaController::class, 'horariosOcupados'])->name('horarios.ocupados');
});
