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
            'products' => 'nullable|array',
            'products.*.product_id' => 'required|exists:products,id',
            'products.*.quantity' => 'required|integer|min:1',
            'products.*.price' => 'required|numeric|min:0',
        ]);

        $errors = [];

        foreach ($validatedData['variants'] ?? [] as $variant) {
            $productVariant = ProductVariant::find($variant['product_variant_id']);
            if ($productVariant && $variant['price'] > $productVariant->sale_price && $variant['price'] > $productVariant->sell_price) {
                $errors[] = "Gía nhập đang cao hơn giá bán ra!";
                continue;
            }
            ProductStock::create([
                'product_id' => $validatedData['product_id'],
                'product_variant_id' => $variant['product_variant_id'],
                'quantity' => $variant['quantity'],
                'price' => $variant['price'],
            ]);
            $productVariant?->increment('stock', $variant['quantity']);
        }

        foreach ($validatedData['products'] ?? [] as $productData) {
            $product = Product::find($productData['product_id']);
            if (!$product) {
                $errors[] = "Không tìm thấy sản phẩm với ID: {$productData['product_id']}";
                continue;
            }
            if ($productData['price'] > $product->sale_price && $productData['price'] > $product->sell_price) {
                $errors[] = "Gía nhập của sản phẩm ID: {$productData['product_id']} cao hơn giá bán ra!";
                continue;
            }
            ProductStock::create([
                'product_id' => $productData['product_id'],
                'product_variant_id' => $productData['product_variant_id'] ?? null,
                'quantity' => $productData['quantity'],
                'price' => $productData['price'],
            ]);
            $product?->increment('stock', $productData['quantity']);
        }

        return response()->json([
            'success' => empty($errors),
            'message' => empty($errors) ? 'Nhập kho thành công!' : 'Có lỗi xảy ra.',
            'errors' => $errors,
        ], empty($errors) ? 201 : 422);
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
