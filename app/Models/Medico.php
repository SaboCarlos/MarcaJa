<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Medico extends Model
{
    protected $table = 'medicos';

    protected $fillable = ['nome', 'especialidade', 'dias', 'sala'];

    public function consultas(): HasMany
    {
        return $this->hasMany(Consulta::class);
    }

    /** Iniciais para o avatar (ex.: "Dra. Ana Macuácua" → "AM"). */
    public function iniciais(): string
    {
        $partes = preg_split('/\s+/', preg_replace('/^(Dra?\.)\s*/u', '', $this->nome));
        $primeira = mb_substr($partes[0] ?? '', 0, 1);
        $ultima = count($partes) > 1 ? mb_substr(end($partes), 0, 1) : '';

        return mb_strtoupper($primeira . $ultima);
    }

    /** Dias da semana em que atende (0 = domingo … 6 = sábado), lidos de textos como "Seg – Sex". */
    public function diasDaSemana(): array
    {
        $mapa = ['dom' => 0, 'seg' => 1, 'ter' => 2, 'qua' => 3, 'qui' => 4, 'sex' => 5, 'sab' => 6, 'sáb' => 6];
        $dias = [];

        foreach (preg_split('/\s*,\s*/u', mb_strtolower((string) $this->dias)) as $parte) {
            $limites = preg_split('/\s*[–-]\s*/u', trim($parte));
            $inicio = $mapa[mb_substr($limites[0], 0, 3)] ?? null;
            $fim = isset($limites[1]) ? ($mapa[mb_substr($limites[1], 0, 3)] ?? null) : $inicio;

            if ($inicio === null || $fim === null) {
                continue;
            }

            for ($d = $inicio; ; $d = ($d + 1) % 7) {
                $dias[] = $d;
                if ($d === $fim) {
                    break;
                }
            }
        }

        return array_values(array_unique($dias));
    }

    public function atendeEm(string $data): bool
    {
        return in_array((int) date('w', strtotime($data)), $this->diasDaSemana(), true);
    }
}
