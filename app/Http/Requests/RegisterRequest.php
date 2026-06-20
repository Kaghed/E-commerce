<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'email' => 'required|string|email|unique:users,email',
            'password' => 'required|string|min:6|confirmed',
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'governorate'=>'required|string|max:255',
            'date_of_birth' => 'required|date',
            'profile_image' => 'required|image|mimes:webp,jpg,jpeg,png,gif|max:10000',
            'role' => 'required|string|max:10',
            'identity_image' => 'required_if:role,seller|image|mimes:webp,jpg,jpeg,png|max:10000',
            'wallet_pin' => 'required|numeric|digits:4'
        ];
    }
}
