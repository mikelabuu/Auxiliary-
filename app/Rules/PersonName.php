<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Human-name validation shared by every booking form (public, manual, walk-in).
 *
 * Names were previously validated as bare 'string|max:255', which let a
 * literal <script> tag be stored as a guest name. Allows unicode letters
 * (Ñ, é, ü…), accents/marks, spaces, and . , ' - ; requires at least one
 * letter so pure punctuation can't pass.
 */
class PersonName implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!is_string($value) || !preg_match("/^(?=.*\p{L})[\p{L}\p{M}\s.,'\-]+$/u", $value)) {
            $fail("The :attribute may only contain letters, spaces, and . , ' - characters.");
        }
    }
}
