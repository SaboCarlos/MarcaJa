<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Só deixa passar os perfis indicados na rota, ex.: ->middleware('perfil:utente,secretaria'). */
class Perfil
{
    public function handle(Request $request, Closure $next, string ...$perfis): Response
    {
        abort_unless(in_array($request->user()?->perfil, $perfis, true), 403, 'Esta área não está disponível para o seu perfil.');

        return $next($request);
    }
}
