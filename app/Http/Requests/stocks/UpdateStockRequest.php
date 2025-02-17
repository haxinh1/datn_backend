<?php

namespace App\Http\Requests\stocks;

use Illuminate\Foundation\Http\FormRequest;

class UpdateStockRequest extends FormRequest
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
        'status' => 'required|integer|in:-1,0,1',
        'reason' => 'nullable|string|max:255',
        'products' => 'required|array',
        'products.*.id' => 'required|exists:products,id',
        'products.*.price' => 'nullable|numeric|min:0',
        'products.*.sell_price' => 'nullable|numeric|min:0',
        'products.*.sale_price' => 'nullable|numeric|min:0',
        'products.*.quantity' => 'nullable|integer|min:1',
        'products.*.variants' => 'nullable|array',
        'products.*.variants.*.id' => 'required|exists:product_variants,id',
        'products.*.variants.*.price' => 'required|numeric|min:0',
        'products.*.variants.*.sell_price' => 'nullable|numeric|min:0',
        'products.*.variants.*.sale_price' => 'nullable|numeric|min:0',
        'products.*.variants.*.quantity' => 'required|integer|min:1',
        ];
    }
}
