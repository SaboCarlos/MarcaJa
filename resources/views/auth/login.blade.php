@extends('auth.base')

@section('titulo', 'Entrar')

@section('formulario')
    <h1 class="h2 fw-bold mb-1">Bem-vindo de volta</h1>
    <p class="text-secondary mb-4">Entre com o seu telefone ou e-mail.</p>

    <form method="POST" action="{{ route('login') }}" novalidate>
        @csrf

        <div class="form-floating mb-3">
            <input id="login" type="text" name="login" value="{{ old('login') }}"
                   class="form-control @error('login') is-invalid @enderror"
                   placeholder="84 123 4567" autocomplete="username" required autofocus>
            <label for="login">Telefone ou e-mail</label>
            @error('login')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>

        <div class="form-floating mb-3">
            <input id="password" type="password" name="password"
                   class="form-control @error('password') is-invalid @enderror"
                   placeholder="Palavra-passe" autocomplete="current-password" required>
            <label for="password">Palavra-passe</label>
            @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>

        <div class="form-check mb-4">
            <input class="form-check-input" type="checkbox" name="remember" id="remember" @checked(old('remember'))>
            <label class="form-check-label" for="remember">Manter sessão iniciada</label>
        </div>

        <button type="submit" class="btn btn-lg mj-gradiente w-100 fw-semibold">Entrar</button>
    </form>

    <p class="text-center text-secondary mt-4 mb-0">
        Ainda não tem conta? <a href="{{ route('register') }}" class="fw-semibold">Criar conta</a>
    </p>
@endsection
