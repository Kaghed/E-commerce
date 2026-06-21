<?php

namespace App\Http\Requests;

use App\Enums\Governorate;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class CreateAdRequest extends FormRequest
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
            'title'=> 'required|string',
            'phone_number' => 'required|regex:/^09[0-9]{8}$/',
            'description'=>'required|string',
            'governorate'=>['required',
                new Enum(Governorate::class)
        ],
            'amount'=>'required|numeric'
        ];
    }
}
