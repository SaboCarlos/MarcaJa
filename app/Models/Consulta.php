<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Consulta extends Model
{
    protected $table = 'consultas';

    protected $fillable = [
        'medico_id',
        'user_id',
        'paciente_nome',
        'paciente_telefone',
        'data',
        'hora',
        'motivo',
        'estado',
        'confirmada_em',
        'serie',
    ];

    protected function casts(): array
    {
        return [
            'confirmada_em' => 'datetime',
        ];
    }

    private const DIAS = ['Domingo', 'Segunda', 'Terça', 'Quarta', 'Quinta', 'Sexta', 'Sábado'];

    private const MESES = ['', 'Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun', 'Jul', 'Ago', 'Set', 'Out', 'Nov', 'Dez'];

    public function medico(): BelongsTo
    {
        return $this->belongsTo(Medico::class);
    }

    public function utente(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Cada perfil só vê as suas consultas:
     * utente → as da sua conta (ou do seu telefone); médico → as da sua agenda; secretaria → todas.
     */
    public function scopeVisivelPara(Builder $query, User $user): void
    {
        if ($user->isSecretaria()) {
            return;
        }

        if ($user->isMedico()) {
            $query->where('medico_id', $user->medico_id ?? 0);

            return;
        }

        $query->where(function (Builder $q) use ($user) {
            $q->where('user_id', $user->id);
            if ($user->telefone) {
                $q->orWhere('paciente_telefone', $user->telefone);
            }
        });
    }

    public function pertenceA(User $user): bool
    {
        return match (true) {
            $user->isSecretaria() => true,
            $user->isMedico() => $user->medico_id !== null && $this->medico_id === $user->medico_id,
            default => $this->user_id === $user->id || ($user->telefone && $this->paciente_telefone === $user->telefone),
        };
    }

    /** Código curto para mostrar ao paciente (ex.: #0007). */
    public function codigo(): string
    {
        return '#' . str_pad((string) $this->id, 4, '0', STR_PAD_LEFT);
    }

    /** Ex.: "Sexta, 06 Nov 2026". */
    public function dataFormatada(): string
    {
        $t = strtotime($this->data);

        return self::DIAS[(int) date('w', $t)] . ', ' . date('d', $t) . ' ' . self::MESES[(int) date('n', $t)] . ' ' . date('Y', $t);
    }

    /** Estado mostrado no ecrã: marcada, confirmada, concluida ou cancelada. */
    public function estadoVisivel(): string
    {
        if ($this->estado === 'cancelada' || $this->estado === 'concluida') {
            return $this->estado;
        }

        if ($this->data < now()->toDateString()) {
            return 'concluida';
        }

        return $this->confirmada_em ? 'confirmada' : 'marcada';
    }

    public function estadoNome(): string
    {
        return [
            'marcada' => 'Marcada',
            'confirmada' => 'Confirmada',
            'concluida' => 'Concluída',
            'cancelada' => 'Cancelada',
        ][$this->estadoVisivel()];
    }

    public function podeCancelar(): bool
    {
        return in_array($this->estadoVisivel(), ['marcada', 'confirmada'], true);
    }

    public function podeConfirmar(): bool
    {
        return $this->estadoVisivel() === 'marcada';
    }

    /** O médico só dá a consulta como realizada no próprio dia. */
    public function podeConcluir(): bool
    {
        return $this->podeCancelar() && $this->data === now()->toDateString();
    }
}
