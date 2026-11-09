<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password', 'perfil', 'telefone', 'medico_id', 'letras_grandes'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    public const UTENTE = 'utente';
    public const SECRETARIA = 'secretaria';
    public const MEDICO = 'medico';

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'letras_grandes' => 'boolean',
        ];
    }

    /** Ficha do médico, quando a conta é de um médico. */
    public function medico(): BelongsTo
    {
        return $this->belongsTo(Medico::class);
    }

    public function consultas(): HasMany
    {
        return $this->hasMany(Consulta::class);
    }

    public function isUtente(): bool
    {
        return $this->perfil === self::UTENTE;
    }

    public function isSecretaria(): bool
    {
        return $this->perfil === self::SECRETARIA;
    }

    public function isMedico(): bool
    {
        return $this->perfil === self::MEDICO;
    }

    public function perfilNome(): string
    {
        return match ($this->perfil) {
            self::SECRETARIA => 'Secretaria',
            self::MEDICO => 'Médico',
            default => 'Utente',
        };
    }
}
