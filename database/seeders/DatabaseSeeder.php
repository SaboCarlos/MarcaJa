<?php

namespace Database\Seeders;

use App\Models\Consulta;
use App\Models\Medico;
use App\Models\User;
use App\Services\Agenda;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    /** Palavra-passe de todas as contas de demonstração. */
    public const PASSWORD = 'marcaja123';

    public function run(): void
    {
        $medicos = [
            ['nome' => 'Dra. Ana Macuácua',    'especialidade' => 'Clínica Geral', 'dias' => 'Seg – Sex', 'sala' => 'Sala 1'],
            ['nome' => 'Dr. Paulo Mabunda',     'especialidade' => 'Clínica Geral', 'dias' => 'Seg – Sáb', 'sala' => 'Sala 2'],
            ['nome' => 'Dr. Carlos Sitoe',      'especialidade' => 'Pediatria',     'dias' => 'Seg – Sex', 'sala' => 'Sala 3'],
            ['nome' => 'Dra. Helena Cossa',     'especialidade' => 'Ginecologia',   'dias' => 'Ter – Sáb', 'sala' => 'Sala 4'],
            ['nome' => 'Dr. Jorge Tembe',       'especialidade' => 'Cardiologia',   'dias' => 'Seg – Qui', 'sala' => 'Sala 5'],
            ['nome' => 'Dra. Marta Nhantumbo',  'especialidade' => 'Dermatologia',  'dias' => 'Qua – Sáb', 'sala' => 'Sala 6'],
        ];

        // Cada médico tem a sua conta: nome.apelido@marcaja.co.mz
        foreach ($medicos as $dados) {
            $medico = Medico::create($dados);
            $partes = explode(' ', Str::ascii(preg_replace('/^Dra?\.\s*/u', '', $medico->nome)));

            User::create([
                'name' => $medico->nome,
                'email' => Str::lower($partes[0] . '.' . end($partes)) . '@marcaja.co.mz',
                'perfil' => User::MEDICO,
                'medico_id' => $medico->id,
                'password' => self::PASSWORD,
            ]);
        }

        // Personas
        $lucia = User::create(['name' => 'Lúcia Mondlane', 'email' => 'lucia@marcaja.co.mz', 'telefone' => '841234567', 'perfil' => User::UTENTE, 'password' => self::PASSWORD]);
        $alberto = User::create(['name' => 'Alberto Nhaca', 'email' => 'alberto@marcaja.co.mz', 'telefone' => '823456789', 'perfil' => User::UTENTE, 'password' => self::PASSWORD]);
        User::create(['name' => 'Rosa Chissano', 'email' => 'rosa@marcaja.co.mz', 'telefone' => '867654321', 'perfil' => User::SECRETARIA, 'password' => self::PASSWORD]);

        $hoje = today();

        // 1. Histórico da Lúcia (aparece como concluída)
        $this->consulta(1, $lucia->id, 'Lúcia Mondlane', '841234567', $hoje->copy()->subDay(), '09:00', 'Dores de cabeça frequentes');

        // 2. Cenário 1: as 10:30 de sexta com o Dr. Carlos Sitoe já estão ocupadas (aparecem riscadas)
        $this->consulta(3, null, 'Inês Bila', '851112233', $hoje->copy()->next(Carbon::FRIDAY), '10:30', 'Tosse persistente');

        // 3–5. Cenário 2: consulta mensal de cardiologia do Alberto (3 meses, só nos dias do Dr. Jorge Tembe)
        $tembe = Medico::find(5);
        $serie = (string) Str::uuid();
        $inicio = $hoje->copy()->next(Carbon::MONDAY);
        for ($i = 0; $i < 3; $i++) {
            $dia = Agenda::proximaDataLivre($tembe, $inicio->copy()->addMonthsNoOverflow($i), '14:00');
            $this->consulta(5, $alberto->id, 'Alberto Nhaca', '823456789', $dia, '14:00', 'Controlo da tensão', $serie);
        }

        // 6. Uma consulta de hoje para a agenda da Dra. Ana e para a Rosa ver na recepção
        $this->consulta(1, null, 'Fátima Langa', '861234567', $hoje, '15:30', 'Consulta de rotina');

        // A próxima consulta marcada (a da Lúcia no Cenário 1) recebe o código #0007
    }

    private function consulta(int $medico, ?int $user, string $nome, string $tel, Carbon $data, string $hora, ?string $motivo, ?string $serie = null): void
    {
        Consulta::create([
            'medico_id' => $medico,
            'user_id' => $user,
            'paciente_nome' => $nome,
            'paciente_telefone' => $tel,
            'data' => $data->toDateString(),
            'hora' => $hora,
            'motivo' => $motivo,
            'estado' => 'marcada',
            'serie' => $serie,
        ]);
    }
}
