<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;

use App\Models\ProductVariant;
use App\Http\Requests\StoreProductVariantRequest;
use App\Http\Requests\UpdateProductVariantRequest;
use Illuminate\Http\Request;

class ProductVariantController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $variants = ProductVariant::all();
        return response()->json([
            'success' => true,
            'message' => "Đây là danh sách biến thể sản phẩm",
            'data' => $variants,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreProductVariantRequest $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(ProductVariant $ProductVariant)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(ProductVariant $ProductVariant)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $variant = ProductVariant::find($id);
        if (!$variant) {
            return response()->json([
                'success' => false,
                'msg' => "Không tìm thấy biến thể",
            ]);
        }
        $variant->update([
            'sku' => $request->input('sku', $variant->sku),
            'sell_price' => $request->input('sell_price', $variant->sell_price),
            'stock' => $request->input('stock', $variant->stock),
            'sale_price' => $request->input('sale_price', $variant->sale_price),
            'sale_price_start_at' => $request->input('sale_price_start_at', $variant->sale_price_start_at) ?? NULL,
            'sale_price_end_at' => $request->input('sale_price_end_at', $variant->sale_price_end_at) ?? NULL,
            'thumbnail' => $request->input('thumbnail', $variant->thumbnail),
        ]);

        return response()->json([
            'success' => true,
            'msg' => "Cập nhật biến thể thành công",
            'variant' => $variant
        ]);
    }


    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ProductVariant $ProductVariant)
    {
        //
    }
    public function active(string $id)
    {
        $variant = ProductVariant::find($id);
        if (!$variant) {
            return response()->json([
                'success' => false,
                'message' => 'Sản phẩm không tìm thấy hoặc xảy ra lỗi!',
            ], 404);
        }
        try {
            if ($variant->is_active == 1) {
                $variant->update(['is_active' => 0]);
            } else {
                $variant->update(['is_active' => 1]);
            }
            return response()->json([
                'success' => true,
                'message' => 'Bạn đã đổi trạng thái thành công!',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Sản phẩm không tìm thấy hoặc xảy ra lỗi!',
            ], 404);
        }
    }
}
