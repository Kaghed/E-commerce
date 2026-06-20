<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateProductRequest extends FormRequest
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
             'title' => 'sometimes|string|max:255',
            'description' => 'sometimes|string',
            'price' => 'sometimes|numeric|min:0',
            'quantity' => 'sometimes|integer|min:0',
            'product_image_url' => 'sometimes|image|mimes:jpeg,png,jpg,gif|max:2048',
            'product_url' => 'sometimes|string|max:255|unique:products,product_url',
            'governorate' => 'sometimes|in:' . implode(',', array_column(\App\Enums\Governorate::cases(), 'value')),
            'category_id' => 'sometimes|exists:categories,id',
            'is_active' => 'sometimes|boolean'
        ];
    }
}
