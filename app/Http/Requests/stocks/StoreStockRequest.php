<?php

namespace App\Http\Requests\stocks;

use Illuminate\Foundation\Http\FormRequest;

class StoreStockRequest extends FormRequest
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
            'user_id' => 'nullable',
            'products' => 'required|array',
            'products.*.id' => 'required|exists:products,id',
            'products.*.price' => 'nullable|numeric|min:0',
            'products.*.quantity' => 'nullable|integer|min:1',
            'products.*.variants' => 'nullable|array',
            'products.*.variants.*.id' => 'required|exists:product_variants,id',
            'products.*.variants.*.price' => 'required|numeric|min:0',
            'products.*.variants.*.quantity' => 'required|integer|min:1',
        ];
    }
}
