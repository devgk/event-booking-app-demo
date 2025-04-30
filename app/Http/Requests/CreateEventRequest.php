<?php

namespace App\Http\Requests;

use App\Rules\LocationCountry;
use Illuminate\Foundation\Http\FormRequest;

class CreateEventRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
		// Permission already managed by middleware
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
		return [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'event_date' => 'required|date|after_or_equal:today',
            'seats_available' => 'required|integer|min:1',
            'location_country' => ['required', 'string', new LocationCountry],
        ];
    }

	/**
     * Get custom validation messages.
     *
     * @return array
     */
    public function messages()
    {
        return [
            'name.required' => 'The event name is required.',
            'event_date.required' => 'The event date is required.',
            'event_date.after_or_equal' => 'The event date must be today or a future date.',
            'seats_available.required' => 'The number of seats available is required.',
            'seats_available.integer' => 'The number of seats available must be a valid integer.',
            'location_country.required' => 'The location country is required.',
        ];
    }
}
