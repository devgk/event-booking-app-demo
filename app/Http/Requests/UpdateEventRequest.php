<?php

namespace App\Http\Requests;

use App\Rules\LocationCountry;
use Illuminate\Foundation\Http\FormRequest;

class UpdateEventRequest extends FormRequest
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
		$id = $this->route('id'); // Get the event ID from the route parameter

        return [
            'name' => 'required|string|max:255',
            'event_date' => 'required|date|unique:events,event_date,' . $id . ',id,name,' . $this->name,
            'location_country' => ['required', 'string', new LocationCountry], // Custom rule for location_country
            'description' => 'nullable|string',
            'seats_available' => 'nullable|integer|min:1', // Ensure it's an integer and has a minimum of 1
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
            'event_date.unique' => 'The event date must be unique for the given event name.',
            'location_country.required' => 'The location country is required.',
            'location_country.string' => 'The location country must be a valid string.',
            'description.string' => 'The description must be a valid string.',
            'seats_available.integer' => 'The seats available must be a valid integer.',
            'seats_available.min' => 'The seats available must be at least 1.',
        ];
    }
}
