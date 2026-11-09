@extends('layout')

@section('titulo', 'Marcar consulta')

@section('conteudo')
    <form method="POST" action="{{ route('consultas.guardar') }}" class="row g-4 align-items-start" id="form-marcar" novalidate>
        @csrf

        <div class="col-lg-8">
            <div class="card">
                <div class="card-body p-4">
                    <h1 class="h2 fw-bold mb-1">Marcar consulta</h1>
                    <p class="text-secondary mb-4">
                        @if (auth()->user()->isSecretaria())
                            Escreva os dados de quem ligou. Se o paciente tiver conta com este telefone, a consulta aparece-lhe automaticamente.
                        @else
                            Preencha os dados abaixo. Leva menos de um minuto.
                        @endif
                    </p>

                    @if ($errors->any())
                        <div class="alert alert-danger fw-semibold">Verifique os campos assinalados a vermelho.</div>
                    @endif

                    <div class="small fw-bold text-uppercase text-primary mb-3">1 · Dados do paciente</div>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="paciente_nome" class="form-label fw-semibold">Nome completo</label>
                            <input type="text" id="paciente_nome" name="paciente_nome" value="{{ old('paciente_nome', $nomePadrao) }}" placeholder="Ex.: Lúcia Mondlane"
                                   class="form-control @error('paciente_nome') is-invalid @enderror" required>
                            @error('paciente_nome')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @else
                                @if (auth()->user()->isUtente())
                                    <div class="form-text">Se a consulta for para outra pessoa (ex.: o seu filho), escreva o nome dela.</div>
                                @endif
                            @enderror
                        </div>
                        <div class="col-md-6">
                            <label for="paciente_telefone" class="form-label fw-semibold">Telefone</label>
                            <input type="tel" id="paciente_telefone" name="paciente_telefone" value="{{ old('paciente_telefone', $telefonePadrao) }}" placeholder="Ex.: 84 123 4567"
                                   class="form-control @error('paciente_telefone') is-invalid @enderror" required>
                            @error('paciente_telefone')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @else
                                <div class="form-text">A recepção usa este número para encontrar a consulta.</div>
                            @enderror
                        </div>
                    </div>

                    <div class="small fw-bold text-uppercase text-primary mt-4 mb-3">2 · Médico e data</div>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="medico_id" class="form-label fw-semibold">Médico</label>
                            <select id="medico_id" name="medico_id" class="form-select @error('medico_id') is-invalid @enderror" required>
                                <option value="">— Escolha o médico —</option>
                                @foreach ($medicos->groupBy('especialidade') as $especialidade => $grupo)
                                    <optgroup label="{{ $especialidade }}">
                                        @foreach ($grupo as $medico)
                                            <option value="{{ $medico->id }}" data-dias="{{ $medico->dias }}" data-semana="{{ implode(',', $medico->diasDaSemana()) }}"
                                                    @selected($selecionado === (string) $medico->id)>
                                                {{ $medico->nome }}
                                            </option>
                                        @endforeach
                                    </optgroup>
                                @endforeach
                            </select>
                            @error('medico_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label for="data" class="form-label fw-semibold">Data</label>
                            <input type="date" id="data" name="data" value="{{ old('data') }}" min="{{ $hoje }}"
                                   class="form-control @error('data') is-invalid @enderror" required>
                            @error('data')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @else
                                <div class="form-text" id="aviso-data">Segunda a sábado, nos dias do médico. Domingo encerrado.</div>
                            @enderror
                        </div>
                    </div>

                    <div class="small fw-bold text-uppercase text-primary mt-4 mb-3">3 · Hora</div>
                    <div class="mj-horas" id="horas">
                        @foreach ($horarios as $h)
                            <input type="radio" class="btn-check" name="hora" id="h{{ str_replace(':', '', $h) }}" value="{{ $h }}" @checked(old('hora') === $h)>
                            <label class="btn btn-outline-primary" for="h{{ str_replace(':', '', $h) }}">{{ $h }}</label>
                        @endforeach
                    </div>
                    @error('hora')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @else
                        <div class="form-text" id="aviso-horas">As horas riscadas já estão ocupadas.</div>
                    @enderror

                    <div class="small fw-bold text-uppercase text-primary mt-4 mb-3">4 · Repetir todos os meses</div>
                    <select id="repetir" name="repetir" class="form-select @error('repetir') is-invalid @enderror" aria-label="Repetir a consulta todos os meses">
                        @foreach ($repeticoes as $n)
                            <option value="{{ $n }}" @selected((int) old('repetir', 0) === $n)>
                                {{ $n === 0 ? 'Não repetir — só esta consulta' : "Todos os meses, durante {$n} meses" }}
                            </option>
                        @endforeach
                    </select>
                    @error('repetir')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @else
                        <div class="form-text">Para consultas de controlo (ex.: tensão arterial). Fica a mesma hora; se o dia estiver ocupado, passa para o dia livre seguinte.</div>
                    @enderror

                    <div class="small fw-bold text-uppercase text-primary mt-4 mb-3">5 · Motivo (opcional)</div>
                    <textarea id="motivo" name="motivo" class="form-control" rows="3" aria-label="Motivo da consulta" placeholder="Ex.: febre há 3 dias, consulta de rotina…">{{ old('motivo') }}</textarea>
                </div>
            </div>
        </div>

        <aside class="col-lg-4 mj-resumo">
            <div class="card mj-elevar">
                <div class="card-body p-4">
                    <h2 class="h4 fw-bold mb-3">Resumo</h2>
                    <dl class="mb-4">
                        <dt class="small fw-semibold text-secondary">Paciente</dt><dd class="fw-bold mb-3" id="r-nome">—</dd>
                        <dt class="small fw-semibold text-secondary">Médico</dt><dd class="fw-bold mb-3" id="r-medico">—</dd>
                        <dt class="small fw-semibold text-secondary">Data e hora</dt><dd class="fw-bold mb-3" id="r-quando">—</dd>
                        <dt class="small fw-semibold text-secondary">Repetição</dt><dd class="fw-bold mb-0" id="r-repetir">—</dd>
                    </dl>
                    <button type="submit" class="btn btn-lg mj-gradiente w-100">Confirmar marcação</button>
                    <p class="small text-secondary mt-3 mb-0">Ao confirmar recebe um código. Pode confirmar a presença ou cancelar até ao dia da consulta.</p>
                </div>
            </div>
        </aside>
    </form>
@endsection

@section('scripts')
<script>
(function () {
    const nome = document.getElementById('paciente_nome');
    const medico = document.getElementById('medico_id');
    const data = document.getElementById('data');
    const repetir = document.getElementById('repetir');
    const horas = document.querySelectorAll('#horas input');
    const avisoData = document.getElementById('aviso-data');
    const textoAvisoData = avisoData ? avisoData.textContent : '';
    const dias = ['Domingo', 'Segunda', 'Terça', 'Quarta', 'Quinta', 'Sexta', 'Sábado'];

    function horaEscolhida() {
        const h = document.querySelector('#horas input:checked');
        return h ? h.value : '';
    }

    // Desativa e risca (classe do Bootstrap) uma hora ocupada
    function bloquearHora(h, bloquear) {
        h.disabled = bloquear;
        h.nextElementSibling.classList.toggle('text-decoration-line-through', bloquear);
        if (bloquear && h.checked) h.checked = false;
    }

    function avisar(texto, erro) {
        if (!avisoData) return;
        avisoData.textContent = texto;
        avisoData.classList.toggle('text-danger', erro);
    }

    function atualizarResumo() {
        document.getElementById('r-nome').textContent = nome.value.trim() || '—';
        const opt = medico.options[medico.selectedIndex];
        document.getElementById('r-medico').textContent = medico.value ? opt.text.trim() + ' (' + opt.parentElement.label + ')' : '—';
        let quando = '—';
        if (data.value) {
            const d = new Date(data.value + 'T00:00:00');
            quando = dias[d.getDay()] + ', ' + data.value.split('-').reverse().join('/');
            if (horaEscolhida()) quando += ' às ' + horaEscolhida();
        }
        document.getElementById('r-quando').textContent = quando;
        const n = Number(repetir.value);
        document.getElementById('r-repetir').textContent = n ? 'Mensal, ' + n + ' consultas' : 'Só esta consulta';
    }

    // Bloqueia horas ocupadas, horas que já passaram (se for hoje) e dias em que o médico não atende
    async function atualizarHoras() {
        horas.forEach(h => bloquearHora(h, false));
        avisar(textoAvisoData, false);
        if (!medico.value || !data.value) { atualizarResumo(); return; }

        const d = new Date(data.value + 'T00:00:00');
        const opt = medico.options[medico.selectedIndex];
        const semana = (opt.dataset.semana || '').split(',').filter(Boolean).map(Number);
        let fechado = '';
        if (d.getDay() === 0) fechado = 'A clínica não atende ao domingo.';
        else if (semana.length && !semana.includes(d.getDay())) fechado = opt.text.trim() + ' só atende ' + opt.dataset.dias + '.';

        if (fechado) {
            horas.forEach(h => bloquearHora(h, true));
            avisar(fechado + ' Escolha outro dia.', true);
            atualizarResumo();
            return;
        }

        let ocupadas = [];
        try {
            const r = await fetch(`{{ route('horarios.ocupados') }}?medico_id=${medico.value}&data=${data.value}`);
            ocupadas = await r.json();
        } catch (e) { /* sem ligação: o servidor valida na mesma */ }

        const agora = new Date();
        const pad = n => String(n).padStart(2, '0');
        const hoje = `${agora.getFullYear()}-${pad(agora.getMonth() + 1)}-${pad(agora.getDate())}` === data.value;
        const horaAgora = pad(agora.getHours()) + ':' + pad(agora.getMinutes());

        horas.forEach(h => bloquearHora(h, ocupadas.includes(h.value) || (hoje && h.value <= horaAgora)));
        atualizarResumo();
    }

    nome.addEventListener('input', atualizarResumo);
    repetir.addEventListener('change', atualizarResumo);
    medico.addEventListener('change', atualizarHoras);
    data.addEventListener('change', atualizarHoras);
    horas.forEach(h => h.addEventListener('change', atualizarResumo));

    atualizarHoras();
})();
</script>
@endsection
