<?php

namespace App\Support;

class Telefone
{
    /** Número móvel moçambicano: 82 a 87 seguido de 7 dígitos. */
    public const REGEX = '/^8[2-7]\d{7}$/';

    /** Deixa só os dígitos e tira o indicativo 258, se vier. */
    public static function limpar(?string $telefone): string
    {
        $digitos = preg_replace('/\D/', '', (string) $telefone);

        if (strlen($digitos) === 12 && str_starts_with($digitos, '258')) {
            $digitos = substr($digitos, 3);
        }

        return $digitos;
    }

    /** "841234567" → "84 123 4567". */
    public static function formatar(?string $telefone): string
    {
        $t = (string) $telefone;

        return strlen($t) === 9 ? substr($t, 0, 2) . ' ' . substr($t, 2, 3) . ' ' . substr($t, 5) : $t;
    }
}
