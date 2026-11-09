<!DOCTYPE html>
<html lang="pt" @class(['letras-grandes' => session('letras_grandes', false)])>
<head>
@include('partials.head')
</head>
<body>
    <div class="mj-auth">
        {{-- Lado da foto --}}
        <aside class="mj-auth-foto">
            <a href="{{ route('inicio') }}" class="d-flex align-items-center gap-2 fw-bold fs-4">
                <span class="mj-logo">+</span> MarcaJá
            </a>
            <figure class="mb-0">
                <blockquote class="fw-semibold mb-3">
                    “Marquei a consulta do meu filho à noite, em casa, em menos de um minuto.”
                </blockquote>
                <figcaption class="small opacity-75">Lúcia M., utente — Matola</figcaption>
            </figure>
        </aside>

        {{-- Lado do formulário --}}
        <main class="mj-auth-form">
            <div>
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <a href="{{ route('inicio') }}" class="small text-decoration-none">← Voltar ao início</a>
                    @include('partials.letras')
                </div>
                @yield('formulario')
            </div>
        </main>
    </div>
</body>
</html>
