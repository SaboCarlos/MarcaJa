<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\Telefone;
use Illuminate\Foundation\Auth\RegistersUsers;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class RegisterController extends Controller implements HasMiddleware
{
    /*
    |--------------------------------------------------------------------------
    | Register Controller (laravel/ui)
    |--------------------------------------------------------------------------
    |
    | Só cria contas de utente. As contas de secretaria e de médico são
    | criadas pela clínica (ver DatabaseSeeder).
    |
    */

    use RegistersUsers;

    protected $redirectTo = '/marcar';

    public static function middleware(): array
    {
        return [new Middleware('guest')];
    }

    protected function validator(array $data)
    {
        $data['telefone'] = Telefone::limpar($data['telefone'] ?? null);

        return Validator::make($data, [
            'name' => ['required', 'string', 'min:3', 'max:255'],
            'telefone' => ['required', 'regex:' . Telefone::REGEX, 'unique:users,telefone'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ], [
            'telefone.regex' => 'Telefone inválido. Use um número moçambicano, ex.: 84 123 4567.',
            'telefone.unique' => 'Já existe uma conta com este telefone.',
        ]);
    }

    protected function create(array $data)
    {
        return User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'telefone' => Telefone::limpar($data['telefone']),
            'perfil' => User::UTENTE,
            'password' => Hash::make($data['password']),
        ]);
    }
}
