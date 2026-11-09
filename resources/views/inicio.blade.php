@extends('layout')

@section('titulo', 'Início')

@section('conteudo')
    @php($podeMarcar = ! auth()->user()?->isMedico())

    <section class="row g-4 align-items-center mb-5">
        <div class="col-lg-7">
            <h1 class="mj-titulo fw-bold mb-3">Marque a sua consulta <span class="mj-texto-gradiente">sem filas</span>, a qualquer hora.</h1>
            <p class="lead text-secondary mb-4">Escolha o médico, a data e a hora que lhe convêm. Receba um código de confirmação e acompanhe as suas consultas na sua conta.</p>
            <div class="d-flex flex-wrap gap-2">
                @if ($podeMarcar)
                    <a href="{{ route('marcar') }}" class="btn btn-lg mj-gradiente">Marcar consulta</a>
                @endif
                @auth
                    <a href="{{ route('consultas') }}" class="btn btn-lg btn-outline-primary">
                        {{ auth()->user()->isMedico() ? 'Ver a minha agenda' : 'Ver as minhas consultas' }}
                    </a>
                @else
                    <a href="{{ route('login') }}" class="btn btn-lg btn-outline-primary">Entrar</a>
                @endauth
            </div>
        </div>
        <div class="col-lg-5">
            <div class="row g-3">
                <div class="col-12">
                    <div class="card mj-gradiente mj-elevar">
                        <div class="card-body">
                            <div class="display-5 fw-bold">{{ $consultasHoje }}</div>
                            <div class="small fw-semibold">consultas marcadas para hoje</div>
                        </div>
                    </div>
                </div>
                <div class="col-6">
                    <div class="card h-100 mj-elevar">
                        <div class="card-body">
                            <div class="display-5 fw-bold text-primary">{{ $totalMedicos }}</div>
                            <div class="small fw-semibold text-secondary">médicos disponíveis</div>
                        </div>
                    </div>
                </div>
                <div class="col-6">
                    <div class="card h-100 mj-elevar">
                        <div class="card-body">
                            <div class="display-5 fw-bold text-primary">{{ $proximas }}</div>
                            <div class="small fw-semibold text-secondary">consultas agendadas</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section>
        <div class="d-flex flex-wrap justify-content-between align-items-end gap-3 mb-3">
            <h2 class="fw-bold mb-0">Os nossos médicos</h2>
            <div class="d-flex flex-wrap gap-2">
                <a href="{{ route('inicio') }}" class="btn btn-sm rounded-pill {{ $filtro ? 'btn-outline-secondary' : 'btn-dark' }}">Todos</a>
                @foreach ($especialidades as $esp)
                    <a href="{{ route('inicio', ['especialidade' => $esp]) }}" class="btn btn-sm rounded-pill {{ $filtro === $esp ? 'btn-dark' : 'btn-outline-secondary' }}">{{ $esp }}</a>
                @endforeach
            </div>
        </div>

        <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-3">
            @forelse ($medicos as $medico)
                <div class="col">
                    <article class="card h-100 mj-elevar">
                        <div class="card-body d-flex flex-column gap-3">
                            <div class="d-flex align-items-center gap-3">
                                <span class="mj-avatar">{{ $medico->iniciais() }}</span>
                                <div>
                                    <h3 class="h6 fw-bold mb-0">{{ $medico->nome }}</h3>
                                    <div class="small fw-semibold text-primary">{{ $medico->especialidade }}</div>
                                </div>
                            </div>
                            <div class="d-flex flex-wrap gap-3 small text-secondary border-top pt-3">
                                <span>📅 {{ $medico->dias }}</span>
                                <span>🕗 08:00 – 16:00</span>
                                @if ($medico->sala)<span>🚪 {{ $medico->sala }}</span>@endif
                            </div>
                            @if ($podeMarcar)
                                <a href="{{ route('marcar', ['medico' => $medico->id]) }}" class="btn btn-primary mt-auto">Marcar com este médico</a>
                            @endif
                        </div>
                    </article>
                </div>
            @empty
                <p class="w-100">Nenhum médico encontrado para esta especialidade.</p>
            @endforelse
        </div>
    </section>
@endsection
