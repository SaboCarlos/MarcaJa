<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Support\Telefone;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class LoginController extends Controller implements HasMiddleware
{
    /*
    |--------------------------------------------------------------------------
    | Login Controller (laravel/ui)
    |--------------------------------------------------------------------------
    |
    | Entra com o e-mail ou com o número de telefone — mais simples para quem,
    | como o Sr. Alberto, não usa e-mail no dia a dia.
    |
    */

    use AuthenticatesUsers;

    protected $redirectTo = '/consultas';

    public static function middleware(): array
    {
        return [
            new Middleware('guest', except: ['logout']),
            new Middleware('auth', only: ['logout']),
        ];
    }

    /** Nome do campo do formulário: aceita e-mail ou telefone. */
    public function username(): string
    {
        return 'login';
    }

    protected function credentials(Request $request): array
    {
        $login = trim((string) $request->input('login'));

        $campo = str_contains($login, '@')
            ? ['email' => $login]
            : ['telefone' => Telefone::limpar($login)];

        return $campo + ['password' => $request->input('password')];
    }
}
