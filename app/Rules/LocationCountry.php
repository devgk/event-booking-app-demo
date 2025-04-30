<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class LocationCountry implements ValidationRule
{
    /**
     * The list of valid ISO 3166-1 alpha-2 country codes.
     *
     * @var array
     */
    protected $validCountryCodes = [
        'US', 'GB', 'CA', 'FR', 'DE', 'IN', 'AU', 'BR', 'IT', 'ES',
    ];

    /**
     * The list of valid country names (ISO names).
     *
     * @var array
     */
    protected $validCountryNames = [
        'United States', 'United Kingdom', 'Canada', 'France', 'Germany', 'India', 'Australia', 'Brazil', 'Italy', 'Spain', 'England'
    ];

    /**
     * Run the validation rule.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @param  \Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     * @return void
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // Check if the value matches a country code or country name
        if (!in_array(strtoupper($value), $this->validCountryCodes) && !in_array($value, $this->validCountryNames)) {
            $fail("The $attribute must be a valid country name or country code.");
        }
    }
}
