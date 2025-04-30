<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BookEventRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
		// Make this accessible to general user without any authentication
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
            'name'      => 'required|string|max:255',
            'email'     => 'required|email',
            'event_id'  => 'required|integer|exists:events,id',
            'address'   => 'nullable|string|max:255',
            'phone'     => 'nullable|string|max:10',
        ];
    }

	/**
     * Custom messages for validation.
     *
     * @return array
     */
    public function messages()
    {
        return [
            'name.required' => 'The name field is required.',
            'email.required' => 'The email field is required.',
            'event_id.required' => 'The event ID field is required.',
            'event_id.exists' => 'The selected event ID is invalid.',
            'phone.max' => 'The phone number should not be more than 10 characters.',
            'address.max' => 'The address should not be more than 255 characters.',
        ];
    }
}
