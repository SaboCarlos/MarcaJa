<!DOCTYPE html>
<html lang="pt" @class(['letras-grandes' => session('letras_grandes', (bool) auth()->user()?->letras_grandes)])>
<head>
@include('partials.head')
</head>
<body>
    <header class="navbar border-bottom sticky-top mj-topo">
        <div class="container gap-2">
            <a href="{{ route('inicio') }}" class="navbar-brand d-flex align-items-center gap-2 fw-bold">
                <span class="mj-logo">+</span>
                <span>Marca<span class="mj-texto-gradiente">Já</span></span>
            </a>

            <nav class="nav nav-pills flex-wrap">
                <a href="{{ route('inicio') }}" class="nav-link {{ request()->routeIs('inicio') ? 'active' : '' }}">Início</a>
                @auth
                    @unless (auth()->user()->isMedico())
                        <a href="{{ route('marcar') }}" class="nav-link {{ request()->routeIs('marcar') ? 'active' : '' }}">Marcar consulta</a>
                    @endunless
                    <a href="{{ route('consultas') }}" class="nav-link {{ request()->routeIs('consultas') ? 'active' : '' }}">
                        {{ match (auth()->user()->perfil) { 'secretaria' => 'Consultas', 'medico' => 'A minha agenda', default => 'Minhas consultas' } }}
                    </a>
                @endauth
            </nav>

            <div class="d-flex align-items-center gap-2">
                @include('partials.letras')
                @auth
                    <span class="small text-secondary d-none d-md-inline">
                        {{ auth()->user()->name }}
                        <span class="badge rounded-pill text-bg-light border">{{ auth()->user()->perfilNome() }}</span>
                    </span>
                    <form method="POST" action="{{ route('logout') }}" class="m-0">
                        @csrf
                        <button type="submit" class="btn btn-sm btn-outline-danger">Sair</button>
                    </form>
                @else
                    <a href="{{ route('login') }}" class="btn btn-sm btn-outline-primary">Entrar</a>
                    <a href="{{ route('register') }}" class="btn btn-sm btn-primary">Criar conta</a>
                @endauth
            </div>
        </div>
    </header>

    <main class="py-4 py-md-5">
        <div class="container">
            @if (session('sucesso'))
                <div class="alert alert-success fw-semibold">{{ session('sucesso') }}</div>
            @endif
            @if (session('erro'))
                <div class="alert alert-danger fw-semibold">{{ session('erro') }}</div>
            @endif

            @yield('conteudo')
        </div>
    </main>

    <footer class="border-top py-3 small text-secondary">
        <div class="container">MarcaJá — Sistema de Marcação de Consultas · Trabalho académico, ESCN {{ date('Y') }}</div>
    </footer>

    @yield('scripts')
</body>
</html>
