<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;

use App\Models\ProductStock;
use App\Http\Requests\StoreProductStockRequest;
use App\Http\Requests\UpdateProductStockRequest;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Http\Request;

class ProductStockController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
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
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'product_id' => 'required|exists:products,id',
            'variants' => 'nullable|array',
            'variants.*.product_variant_id' => 'nullable|exists:product_variants,id',
            'variants.*.quantity' => 'nullable|integer|min:1',
            'variants.*.price' => 'nullable|numeric|min:0',
            'quantity' => 'nullable|integer|min:1', // Số lượng cho sản phẩm đơn
            'price' => 'nullable|numeric|min:0', // Giá nhập cho sản phẩm đơn
        ]);
    
        $errors = [];
    
        if (!empty($validatedData['variants'])) {
            // Nếu nhập kho có biến thể
            foreach ($validatedData['variants'] as $variant) {
                $productVariant = ProductVariant::find($variant['product_variant_id']);
    
                if ($productVariant && $variant['price'] > $productVariant->sale_price && $variant['price'] > $productVariant->sell_price) {
                    $errors[] = "Gía nhập đang cao hơn giá bán ra !";
                    continue;
                }
    
                ProductStock::create([
                    'product_id' => $validatedData['product_id'],
                    'product_variant_id' => $variant['product_variant_id'],
                    'quantity' => $variant['quantity'],
                    'price' => $variant['price'],
                ]);
    
                // Cập nhật tổng số lượng của biến thể
                if ($productVariant) {
                    $productVariant->increment('stock', $variant['quantity']);
                }
            }
        } else {
            // Nếu nhập kho sản phẩm đơn
            if (!isset($validatedData['quantity']) || !isset($validatedData['price'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cần cung cấp số lượng và giá nhập cho sản phẩm đơn.'
                ], 422);
            }
    
            $product = Product::find($validatedData['product_id']);
    
            if ($product && $validatedData['price'] > $product->sale_price && $validatedData['price'] > $product->sell_price) {
                return response()->json([
                    'success' => false,
                    'message' => "Gía nhập đang cao hơn giá bán ra!"
                ], 422);
            }
    
            ProductStock::create([
                'product_id' => $validatedData['product_id'],
                'product_variant_id' => null,
                'quantity' => $validatedData['quantity'],
                'price' => $validatedData['price'],
            ]);
        }
    
        if (!empty($errors)) {
            return response()->json([
                'success' => false,
                'errors' => $errors,
            ], 422);
        }
    
        return response()->json([
            'success' => true,
            'message' => 'Nhập kho thành công!',
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(ProductStock $ProductStock)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(ProductStock $ProductStock)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateProductStockRequest $request, ProductStock $ProductStock)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ProductStock $ProductStock)
    {
        //
    }
}
