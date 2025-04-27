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
            'slug' => 'nullable|string|max:255|unique:products,slug',
            'views' => 'nullable|integer|min:0',
            'content' => 'nullable|string',
            'thumbnail' => 'required|string',
            'sku' => 'nullable|string|unique:products,sku|max:50',
            'sell_price' => 'nullable|numeric',
            'sale_price' => 'nullable|numeric|lt:sell_price',
            'sale_price_start_at' => 'nullable|date|before_or_equal:sale_price_end_at',
            'sale_price_end_at' => 'nullable|date|after_or_equal:sale_price_start_at',
            'is_active' => 'nullable|boolean',
        ];
    }
    public function messages(): array
{
    return [
        'brand_id.exists' => 'Thương hiệu không hợp lệ.',
        'category_id.exists' => 'Danh mục không hợp lệ.',
        'name.required' => 'Tên sản phẩm là bắt buộc.',
        'name.string' => 'Tên sản phẩm phải là chuỗi.',
        'name.max' => 'Tên sản phẩm không được vượt quá 255 ký tự.',
        'slug.string' => 'Slug phải là chuỗi.',
        'slug.max' => 'Slug không được vượt quá 255 ký tự.',
        'slug.unique' => 'Slug đã tồn tại.',
        'views.integer' => 'Lượt xem phải là số nguyên.',
        'views.min' => 'Lượt xem phải lớn hơn hoặc bằng 0.',
        'content.string' => 'Nội dung phải là chuỗi.',
        'thumbnail.required' => 'Ảnh đại diện là bắt buộc.',
        'thumbnail.string' => 'Ảnh đại diện phải là chuỗi.',
        'sku.string' => 'Mã SKU phải là chuỗi.',
        'sku.max' => 'Mã SKU không được vượt quá 50 ký tự.',
        'sku.unique' => 'Mã SKU đã tồn tại.',
        'sell_price.numeric' => 'Giá bán phải là số.',
        'sale_price.numeric' => 'Giá khuyến mãi phải là số.',
        'sale_price.lt' => 'Giá khuyến mãi phải nhỏ hơn giá bán.',
        'sale_price_start_at.date' => 'Ngày bắt đầu khuyến mãi không hợp lệ.',
        'sale_price_start_at.before_or_equal' => 'Ngày bắt đầu khuyến mãi phải trước hoặc bằng ngày kết thúc.',
        'sale_price_end_at.date' => 'Ngày kết thúc khuyến mãi không hợp lệ.',
        'sale_price_end_at.after_or_equal' => 'Ngày kết thúc khuyến mãi phải sau hoặc bằng ngày bắt đầu.',
        'is_active.boolean' => 'Trạng thái hoạt động không hợp lệ.',
    ];
}
}
