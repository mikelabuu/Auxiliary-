<?php

namespace App\Rules;

use App\Services\PsgcDirectory;
use Closure;
use Illuminate\Contracts\Validation\DataAwareRule;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * An address field posted by a booking form as "CODE|NAME".
 *
 * These were validated as bare `string|max:255` while the controllers read
 * only the NAME half, so a malformed value did not fail — it quietly stored an
 * address with a level missing (a booking whose barangay simply vanished), and
 * a crafted POST could write arbitrary text where a barangay belongs. The
 * address is what the front desk uses to identify a guest, so neither failure
 * is visible until it matters.
 *
 * Checks the shape first, then that the code is a real place. Barangay codes
 * are checked against the city posted alongside them, so a valid barangay from
 * some other city is rejected too.
 *
 * When the gazetteer is missing the existence check is skipped and only the
 * shape is enforced: PsgcController answers the same situation with empty
 * dropdowns rather than a 500, and blocking every checkout because
 * `psgc:sync` has not run would be the worse failure.
 */
class PsgcCode implements ValidationRule, DataAwareRule
{
    private array $data = [];

    /**
     * @param string $level One of the PsgcDirectory levels: regions,
     *                      provinces, cities, barangays.
     */
    public function __construct(private readonly string $level)
    {
    }

    public function setData(array $data): static
    {
        $this->data = $data;

        return $this;
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // Nine digits, a pipe, then a label with something in it.
        if (! is_string($value) || ! preg_match('/^\d{9}\|.*\S/', $value)) {
            $fail('Please choose the :attribute from the address list.');

            return;
        }

        $directory = app(PsgcDirectory::class);

        if (! $directory->isAvailable()) {
            return;
        }

        if (! $directory->exists($this->level, $value, $this->data['city_code'] ?? null)) {
            $fail('That :attribute is not a recognised Philippine address.');
        }
    }
}
