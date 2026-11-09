@extends('layout')

@section('titulo', $titulo)

@section('conteudo')
    @php
        $user = auth()->user();
        $hoje = now()->toDateString();
        $amanha = now()->addDay()->toDateString();
        $coresEstado = ['marcada' => 'text-bg-primary', 'confirmada' => 'text-bg-success', 'concluida' => 'text-bg-secondary', 'cancelada' => 'text-bg-danger'];
        $meses = ['', 'Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun', 'Jul', 'Ago', 'Set', 'Out', 'Nov', 'Dez'];
        $comTelefone = fn (array $q) => $telefone !== '' ? $q + ['telefone' => $telefone] : $q;
    @endphp

    <div class="d-flex flex-wrap justify-content-between align-items-end gap-3 mb-4">
        <div>
            <h1 class="h2 fw-bold mb-1">{{ $titulo }}</h1>
            <p class="text-secondary mb-0">{{ $subtitulo }}</p>
        </div>
        @unless ($user->isMedico())
            <a href="{{ route('marcar') }}" class="btn mj-gradiente">+ Marcar consulta</a>
        @endunless
    </div>

    {{-- Filtros: secretaria (telefone + dia) e médico (dia) --}}
    @if ($equipa)
        <form method="GET" action="{{ route('consultas') }}" class="card card-body mb-4">
            <div class="row g-2 align-items-end">
                @if ($user->isSecretaria())
                    <div class="col-sm-6 col-lg-4">
                        <label for="f-telefone" class="form-label small fw-semibold mb-1">Telefone do paciente</label>
                        <input type="tel" id="f-telefone" name="telefone" value="{{ \App\Support\Telefone::formatar($telefone) }}" class="form-control" placeholder="Ex.: 82 345 6789">
                    </div>
                @endif
                <div class="col-sm-6 col-lg-3">
                    <label for="f-data" class="form-label small fw-semibold mb-1">Dia</label>
                    <input type="date" id="f-data" name="data" value="{{ $data }}" class="form-control">
                </div>
                <div class="col-12 col-lg-auto">
                    <button type="submit" class="btn btn-primary w-100">Pesquisar</button>
                </div>
                <div class="col-12 col-lg d-flex flex-wrap gap-2 justify-content-lg-end">
                    <a href="{{ route('consultas', $comTelefone(['data' => $hoje])) }}" class="btn btn-sm rounded-pill {{ $data === $hoje ? 'btn-dark' : 'btn-outline-secondary' }}">Hoje</a>
                    <a href="{{ route('consultas', $comTelefone(['data' => $amanha])) }}" class="btn btn-sm rounded-pill {{ $data === $amanha ? 'btn-dark' : 'btn-outline-secondary' }}">Amanhã</a>
                    <a href="{{ route('consultas', $comTelefone(['data' => ''])) }}" class="btn btn-sm rounded-pill {{ $data === null ? 'btn-dark' : 'btn-outline-secondary' }}">Todas</a>
                </div>
            </div>
        </form>
    @endif

    @if ($consultas->isNotEmpty())
        @php
            $contagem = $consultas->countBy(fn ($c) => $c->estadoVisivel());
        @endphp
        <div class="d-flex flex-wrap gap-2 mb-3">
            <span class="badge rounded-pill text-bg-light border">{{ $consultas->count() }} no total</span>
            <span class="badge rounded-pill text-bg-light border">{{ $contagem['marcada'] ?? 0 }} marcadas</span>
            <span class="badge rounded-pill text-bg-light border">{{ $contagem['confirmada'] ?? 0 }} confirmadas</span>
            <span class="badge rounded-pill text-bg-light border">{{ $contagem['concluida'] ?? 0 }} concluídas</span>
            <span class="badge rounded-pill text-bg-light border">{{ $contagem['cancelada'] ?? 0 }} canceladas</span>
        </div>

        <div class="d-flex flex-column gap-3">
            @foreach ($consultas as $c)
                @php
                    $estado = $c->estadoVisivel();
                    $t = strtotime($c->data);
                @endphp
                <article class="card mj-elevar mj-consulta" data-estado="{{ $estado }}">
                    <div class="mj-dia">
                        <b>{{ date('d', $t) }}</b>
                        <span class="small fw-bold text-uppercase text-secondary">{{ $meses[(int) date('n', $t)] }} {{ date('Y', $t) }}</span>
                        <div class="fw-semibold mt-1">{{ $c->hora }}</div>
                    </div>

                    <div>
                        @if ($user->isMedico())
                            <h3 class="h6 fw-bold mb-1">{{ $c->paciente_nome }}</h3>
                            <div class="small text-secondary">{{ $c->dataFormatada() }} · {{ $c->medico->sala }} · Tel. {{ \App\Support\Telefone::formatar($c->paciente_telefone) }}</div>
                        @else
                            <h3 class="h6 fw-bold mb-1">{{ $c->medico->nome }}</h3>
                            <div class="small text-secondary">{{ $c->medico->especialidade }} · {{ $c->dataFormatada() }} · {{ $c->medico->sala }}</div>
                            <div class="small text-secondary">Paciente: {{ $c->paciente_nome }} ({{ \App\Support\Telefone::formatar($c->paciente_telefone) }})</div>
                        @endif
                        @if ($c->motivo)
                            <div class="small mt-2">“{{ $c->motivo }}”</div>
                        @endif
                        @if ($c->serie)
                            <span class="badge rounded-pill bg-info-subtle text-info-emphasis mt-2">↻ Consulta mensal</span>
                        @endif
                    </div>

                    <div class="mj-acoes">
                        <span class="badge rounded-pill text-uppercase {{ $coresEstado[$estado] }}">{{ $c->estadoNome() }}</span>
                        <span class="font-monospace small text-secondary">{{ $c->codigo() }}</span>
                        <div class="d-flex flex-wrap gap-2 justify-content-end">
                            @if ($c->podeConfirmar() && ! $user->isMedico())
                                <form method="POST" action="{{ route('consultas.confirmar', $c) }}" class="m-0">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="btn btn-success btn-sm">Confirmar presença</button>
                                </form>
                            @endif
                            @if ($c->podeConcluir() && ! $user->isUtente())
                                <form method="POST" action="{{ route('consultas.concluir', $c) }}" class="m-0">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="btn btn-outline-secondary btn-sm">Realizada</button>
                                </form>
                            @endif
                            @if ($c->podeCancelar())
                                <form method="POST" action="{{ route('consultas.cancelar', $c) }}" class="m-0" onsubmit="return confirm('Cancelar a consulta {{ $c->codigo() }}?');">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="btn btn-outline-danger btn-sm">Cancelar</button>
                                </form>
                            @endif
                        </div>
                    </div>
                </article>
            @endforeach
        </div>
    @else
        <div class="card text-center">
            <div class="card-body py-5">
                <h2 class="h4 fw-bold mb-2">Nenhuma consulta encontrada</h2>
                <p class="text-secondary mb-4">
                    @if ($equipa)
                        Não há consultas para esta pesquisa. Experimente “Todas”.
                    @else
                        Ainda não tem consultas marcadas.
                    @endif
                </p>
                @unless ($user->isMedico())
                    <a href="{{ route('marcar') }}" class="btn btn-primary">Marcar uma consulta</a>
                @endunless
            </div>
        </div>
    @endif
@endsection
