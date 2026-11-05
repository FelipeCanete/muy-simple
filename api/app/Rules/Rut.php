<?php

namespace App\Rules;

use App\Support\Rut as RutSupport;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class Rut implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || ! RutSupport::isValid($value)) {
            $fail('El RUT no es válido.');
        }
    }
}
