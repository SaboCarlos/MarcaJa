@extends('auth.base')

@section('titulo', 'Criar conta')

@section('formulario')
    <h1 class="h2 fw-bold mb-1">Criar conta</h1>
    <p class="text-secondary mb-4">Para marcar e acompanhar as suas consultas.</p>

    <form method="POST" action="{{ route('register') }}" novalidate>
        @csrf

        <div class="form-floating mb-3">
            <input id="name" type="text" name="name" value="{{ old('name') }}"
                   class="form-control @error('name') is-invalid @enderror" placeholder="Nome" autocomplete="name" required autofocus>
            <label for="name">Nome completo</label>
            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>

        <div class="form-floating mb-3">
            <input id="telefone" type="tel" name="telefone" value="{{ old('telefone') }}"
                   class="form-control @error('telefone') is-invalid @enderror" placeholder="84 123 4567" autocomplete="tel" required>
            <label for="telefone">Telefone</label>
            @error('telefone')
                <div class="invalid-feedback">{{ $message }}</div>
            @else
                <div class="form-text">Pode entrar com este número em vez do e-mail.</div>
            @enderror
        </div>

        <div class="form-floating mb-3">
            <input id="email" type="email" name="email" value="{{ old('email') }}"
                   class="form-control @error('email') is-invalid @enderror" placeholder="nome@exemplo.com" autocomplete="email" required>
            <label for="email">E-mail</label>
            @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>

        <div class="row g-3 mb-4">
            <div class="col-sm-6">
                <div class="form-floating">
                    <input id="password" type="password" name="password"
                           class="form-control @error('password') is-invalid @enderror" placeholder="Palavra-passe" autocomplete="new-password" required>
                    <label for="password">Palavra-passe</label>
                    @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>
            <div class="col-sm-6">
                <div class="form-floating">
                    <input id="password-confirm" type="password" name="password_confirmation"
                           class="form-control" placeholder="Repetir" autocomplete="new-password" required>
                    <label for="password-confirm">Repetir</label>
                </div>
            </div>
        </div>

        <button type="submit" class="btn btn-lg mj-gradiente w-100 fw-semibold">Criar conta</button>
    </form>

    <p class="text-center text-secondary mt-4 mb-0">
        Já tem conta? <a href="{{ route('login') }}" class="fw-semibold">Entrar</a>
    </p>
@endsection
