<?php

namespace App\Services;

use App\Models\Consulta;
use App\Models\Medico;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/** Regras de horário partilhadas pelo formulário, pelas consultas mensais e pelos seeders. */
class Agenda
{
    /** Horários de atendimento: 08:00 às 15:30, de 30 em 30 minutos. */
    public static function horarios(): array
    {
        $lista = [];
        for ($m = 8 * 60; $m <= 15 * 60 + 30; $m += 30) {
            $lista[] = sprintf('%02d:%02d', intdiv($m, 60), $m % 60);
        }

        return $lista;
    }

    /** Horas já ocupadas (consultas não canceladas) de um médico numa data. */
    public static function horasOcupadas(int $medicoId, string $data): Collection
    {
        return Consulta::where('medico_id', $medicoId)
            ->where('data', $data)
            ->where('estado', '!=', 'cancelada')
            ->pluck('hora');
    }

    /**
     * Verifica se o médico pode receber uma consulta nesta data e hora.
     * Devolve null se estiver tudo bem, ou [campo => mensagem] com o problema.
     */
    public static function problema(Medico $medico, string $data, string $hora): ?array
    {
        if ((int) date('w', strtotime($data)) === 0) {
            return ['data' => 'A clínica não atende ao domingo. Escolha outro dia.'];
        }

        if (! $medico->atendeEm($data)) {
            return ['data' => $medico->nome . ' só atende ' . $medico->dias . '. Escolha outro dia.'];
        }

        if ($data === now()->toDateString() && $hora <= now()->format('H:i')) {
            return ['hora' => 'Esta hora já passou. Escolha uma hora mais tarde.'];
        }

        if (self::horasOcupadas($medico->id, $data)->contains($hora)) {
            return ['hora' => 'Este horário já está ocupado para este médico. Escolha outra hora.'];
        }

        return null;
    }

    /** Primeiro dia livre, a partir de $desde, em que o médico tem esta hora disponível. */
    public static function proximaDataLivre(Medico $medico, Carbon $desde, string $hora, int $limiteDias = 14): ?Carbon
    {
        for ($i = 0; $i <= $limiteDias; $i++) {
            $dia = $desde->copy()->addDays($i);

            if (self::problema($medico, $dia->toDateString(), $hora) === null) {
                return $dia;
            }
        }

        return null;
    }
}
