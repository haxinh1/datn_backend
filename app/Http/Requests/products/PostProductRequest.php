<?php

namespace App\Http\Requests\products;

use Illuminate\Foundation\Http\FormRequest;

class PostProductRequest extends FormRequest
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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'brand_id' => 'nullable|exists:brands,id',
            'category_id' => 'nullable|exists:categories,id',
            'name' => 'required|string|max:255',
            'name_link' => 'nullable|url|max:255',
            'slug' => 'nullable|string|max:255|unique:products,slug',
            'views' => 'nullable|integer|min:0',
            'content' => 'nullable|string',
            'thumbnail' => 'required|string',
            'sku' => 'nullable|string|unique:products,sku|max:50',
            'sell_price' => 'required|numeric',
            'sale_price' => 'nullable|numeric|lt:sell_price',
            'sale_price_start_at' => 'nullable|date|before_or_equal:sale_price_end_at',
            'sale_price_end_at' => 'nullable|date|after_or_equal:sale_price_start_at',
            'is_active' => 'nullable|boolean',
        ];
    }
}
