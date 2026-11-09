<?php

namespace App\Http\Controllers;

use App\Models\Consulta;
use App\Models\Medico;
use App\Models\User;
use App\Services\Agenda;
use App\Support\Telefone;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ConsultaController extends Controller
{
    /** Opções de repetição mensal: 0 = só esta consulta. */
    public const REPETICOES = [0, 3, 6, 12];

    /* ---------------- TELA 1: Início ---------------- */

    public function inicio(Request $request)
    {
        $especialidades = Medico::query()->distinct()->orderBy('especialidade')->pluck('especialidade');
        $filtro = $request->query('especialidade');

        $medicos = Medico::query()
            ->when($filtro, fn ($q) => $q->where('especialidade', $filtro))
            ->orderBy('especialidade')
            ->orderBy('nome')
            ->get();

        return view('inicio', [
            'medicos' => $medicos,
            'especialidades' => $especialidades,
            'filtro' => $filtro,
            'totalMedicos' => Medico::count(),
            'consultasHoje' => Consulta::where('estado', 'marcada')->where('data', now()->toDateString())->count(),
            'proximas' => Consulta::where('estado', 'marcada')->where('data', '>=', now()->toDateString())->count(),
        ]);
    }

    /* ---------------- TELA 2: Marcar consulta ---------------- */

    public function marcar(Request $request)
    {
        $user = $request->user();

        return view('marcar', [
            'medicos' => Medico::orderBy('especialidade')->orderBy('nome')->get(),
            'horarios' => Agenda::horarios(),
            'selecionado' => (string) old('medico_id', $request->query('medico', '')),
            'hoje' => now()->toDateString(),
            'repeticoes' => self::REPETICOES,
            // O utente já vem preenchido; a secretaria escreve os dados de quem liga
            'nomePadrao' => $user->isUtente() ? $user->name : '',
            'telefonePadrao' => $user->isUtente() ? Telefone::formatar($user->telefone) : '',
        ]);
    }

    public function guardar(Request $request)
    {
        $user = $request->user();
        $request->merge(['paciente_telefone' => Telefone::limpar($request->input('paciente_telefone'))]);

        $dados = $request->validate([
            'paciente_nome' => ['required', 'string', 'min:3', 'max:100'],
            'paciente_telefone' => ['required', 'regex:' . Telefone::REGEX],
            'medico_id' => ['required', 'exists:medicos,id'],
            'data' => ['required', 'date_format:Y-m-d', 'after_or_equal:today'],
            'hora' => ['required', Rule::in(Agenda::horarios())],
            'motivo' => ['nullable', 'string', 'max:255'],
            'repetir' => ['nullable', 'integer', Rule::in(self::REPETICOES)],
        ], [
            'paciente_nome.required' => 'Indique o nome do paciente.',
            'paciente_nome.min' => 'O nome deve ter pelo menos 3 letras.',
            'paciente_telefone.required' => 'Indique um número de telefone.',
            'paciente_telefone.regex' => 'Telefone inválido. Use um número moçambicano, ex.: 84 123 4567.',
            'medico_id.required' => 'Escolha o médico.',
            'medico_id.exists' => 'O médico escolhido não existe.',
            'data.required' => 'Escolha a data da consulta.',
            'data.after_or_equal' => 'A data não pode ser no passado.',
            'hora.required' => 'Escolha a hora.',
            'hora.in' => 'Hora fora do horário de atendimento.',
            'repetir.in' => 'Escolha uma das opções de repetição.',
        ]);

        $medico = Medico::findOrFail($dados['medico_id']);

        // Domingo, dia em que o médico não atende, hora passada ou já ocupada
        if ($erro = Agenda::problema($medico, $dados['data'], $dados['hora'])) {
            return back()->withInput()->withErrors($erro);
        }

        // A consulta fica na conta do utente. Se foi a secretaria a marcar, liga-se à conta com o mesmo telefone (se existir).
        $dono = $user->isUtente()
            ? $user->id
            : User::where('perfil', User::UTENTE)->where('telefone', $dados['paciente_telefone'])->value('id');

        $meses = (int) ($dados['repetir'] ?? 0);
        $base = collect($dados)->only(['medico_id', 'paciente_nome', 'paciente_telefone', 'hora', 'motivo'])->all()
            + ['user_id' => $dono, 'estado' => 'marcada', 'serie' => $meses > 1 ? (string) Str::uuid() : null];

        [$consulta, $seguintes, $semVaga] = DB::transaction(function () use ($base, $dados, $medico, $meses) {
            $consulta = Consulta::create($base + ['data' => $dados['data']]);
            $seguintes = [];
            $semVaga = [];

            // Consultas mensais: mesma hora, no mesmo dia do mês ou no primeiro dia livre a seguir
            for ($i = 1; $i < $meses; $i++) {
                $alvo = Carbon::parse($dados['data'])->addMonthsNoOverflow($i);
                $dia = Agenda::proximaDataLivre($medico, $alvo, $dados['hora']);

                if ($dia) {
                    $seguintes[] = Consulta::create($base + ['data' => $dia->toDateString()]);
                } else {
                    $semVaga[] = $alvo->format('m/Y');
                }
            }

            return [$consulta, $seguintes, $semVaga];
        });

        $mensagem = 'Consulta marcada com sucesso! Código ' . $consulta->codigo() . '.';
        if ($seguintes) {
            $mensagem .= ' Também ficaram marcadas as consultas mensais de '
                . collect($seguintes)->map(fn ($c) => date('d/m/Y', strtotime($c->data)))->join(', ', ' e ') . '.';
        }
        if ($semVaga) {
            $mensagem .= ' Não houve vaga em ' . implode(', ', $semVaga) . '.';
        }

        return redirect()
            ->route('consultas', $user->isUtente() ? [] : ['telefone' => $consulta->paciente_telefone])
            ->with('sucesso', $mensagem);
    }

    /** JSON usado pelo formulário para bloquear horas já ocupadas. */
    public function horariosOcupados(Request $request): JsonResponse
    {
        return response()->json(
            Agenda::horasOcupadas($request->integer('medico_id'), (string) $request->query('data'))
        );
    }

    /* ---------------- TELA 3: Consultas (cada perfil vê as suas) ---------------- */

    public function consultas(Request $request)
    {
        $user = $request->user();
        $equipa = ! $user->isUtente();   // secretaria e médico podem filtrar
        $telefone = $user->isSecretaria() ? Telefone::limpar($request->query('telefone')) : '';

        // Secretaria e médico abrem no dia de hoje; "Todas" envia data vazia
        $data = $request->query('data');
        if ($equipa && ! $request->has('data') && $telefone === '') {
            $data = now()->toDateString();
        }
        if (! is_string($data) || ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $data)) {
            $data = null;
        }

        $consultas = Consulta::with('medico')
            ->visivelPara($user)
            ->when($equipa && $telefone !== '', fn ($q) => $q->where('paciente_telefone', $telefone))
            ->when($equipa && $data, fn ($q) => $q->where('data', $data))
            ->orderByDesc('data')
            ->orderBy('hora')
            ->get();

        [$titulo, $subtitulo] = match ($user->perfil) {
            User::SECRETARIA => ['Consultas', 'Pesquise pelo telefone do paciente ou veja as consultas de um dia.'],
            User::MEDICO => ['A minha agenda', ($user->medico?->nome ?? 'Médico') . ' · ' . ($user->medico?->especialidade ?? '')],
            default => ['Minhas consultas', 'As consultas que marcou com a sua conta ou com o número ' . Telefone::formatar($user->telefone) . '.'],
        };

        return view('consultas', [
            'consultas' => $consultas,
            'telefone' => $telefone,
            'data' => $data,
            'equipa' => $equipa,
            'titulo' => $titulo,
            'subtitulo' => $subtitulo,
        ]);
    }

    public function cancelar(Request $request, Consulta $consulta)
    {
        abort_unless($consulta->pertenceA($request->user()), 403);

        if (! $consulta->podeCancelar()) {
            return back()->with('erro', 'Esta consulta já não pode ser cancelada.');
        }

        $consulta->update(['estado' => 'cancelada']);

        return back()->with('sucesso', 'Consulta ' . $consulta->codigo() . ' cancelada. O horário ficou livre.');
    }

    /** O utente confirma que vai (ou a secretaria regista a confirmação feita ao telefone). */
    public function confirmar(Request $request, Consulta $consulta)
    {
        abort_unless($consulta->pertenceA($request->user()), 403);

        if (! $consulta->podeConfirmar()) {
            return back()->with('erro', 'Esta consulta não pode ser confirmada.');
        }

        $consulta->update(['confirmada_em' => now()]);

        return back()->with('sucesso', 'Presença confirmada na consulta ' . $consulta->codigo() . '.');
    }

    /** O médico (ou a secretaria) dá a consulta de hoje como realizada. */
    public function concluir(Request $request, Consulta $consulta)
    {
        abort_unless($consulta->pertenceA($request->user()), 403);

        if (! $consulta->podeConcluir()) {
            return back()->with('erro', 'Só pode dar como realizada uma consulta de hoje que ainda esteja marcada.');
        }

        $consulta->update(['estado' => 'concluida']);

        return back()->with('sucesso', 'Consulta ' . $consulta->codigo() . ' realizada.');
    }

    /** Liga/desliga as letras grandes (fica guardado na conta, se houver sessão iniciada). */
    public function letras(Request $request)
    {
        $user = $request->user();
        $ativo = ! $request->session()->get('letras_grandes', (bool) $user?->letras_grandes);

        $request->session()->put('letras_grandes', $ativo);
        $user?->update(['letras_grandes' => $ativo]);

        return back();
    }
}
