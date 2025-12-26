<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ValidEcuadorianCedula implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $cedula = preg_replace('/\D+/', '', (string) $value);

        if (strlen($cedula) !== 10) {
            $fail('La cédula debe tener 10 dígitos.');
            return;
        }

        // Provincia 01..24
        $prov = (int) substr($cedula, 0, 2);
        if ($prov < 1 || $prov > 24) {
            $fail('La cédula no tiene un código de provincia válido.');
            return;
        }

        // Tercer dígito 0..5 para persona natural
        $tercer = (int) $cedula[2];
        if ($tercer < 0 || $tercer > 5) {
            $fail('La cédula no es válida (tercer dígito inválido).');
            return;
        }

        // Algoritmo módulo 10
        $coef = [2,1,2,1,2,1,2,1,2];
        $sum = 0;

        for ($i = 0; $i < 9; $i++) {
            $p = ((int)$cedula[$i]) * $coef[$i];
            if ($p >= 10) $p -= 9;
            $sum += $p;
        }

        $verificador = (int) $cedula[9];
        $residuo = $sum % 10;
        $digito = $residuo === 0 ? 0 : 10 - $residuo;

        if ($digito !== $verificador) {
            $fail('La cédula ecuatoriana no es válida.');
        }
    }
}
