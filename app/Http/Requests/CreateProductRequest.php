<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class CreateProductRequest extends FormRequest
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
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'quantity' => 'required|integer|min:0',
            'product_image_url' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'product_url' => 'nullable|string|max:255|unique:products,product_url',
            'governorate' => 'required|in:' . implode(',', array_column(\App\Enums\Governorate::cases(), 'value')),
            'category_id' => 'required|exists:categories,id'
        ];
    }
}
