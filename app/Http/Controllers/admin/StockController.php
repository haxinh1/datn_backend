<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductStock;
use App\Models\ProductVariant;
use App\Models\Stock;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StockController extends Controller
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
            'user_id' => 'nullable',
            'variants' => 'nullable|array',
            'variants.*.product_variant_id' => 'nullable|exists:product_variants,id',
            'variants.*.quantity' => 'nullable|integer|min:1',
            'variants.*.price' => 'nullable|numeric|min:0',
            'products' => 'nullable|array',
            'products.*.product_id' => 'required|exists:products,id',
            'products.*.quantity' => 'required|integer|min:1',
            'products.*.price' => 'required|numeric|min:0',
        ]);

        DB::beginTransaction();
        try {
            // Tạo bản ghi nhập kho
            $stock = Stock::create([
                'user_id' => $validatedData['user_id'] ?? 1,
                'total_amount' => 0, // Tạm thời, sẽ cập nhật sau
                'status' => 0,
            ]);

            $totalAmount = 0;
            $errors = [];

            // Xử lý nhập kho cho các biến thể sản phẩm
            foreach ($validatedData['variants'] ?? [] as $variant) {
                $productVariant = ProductVariant::find($variant['product_variant_id']);
                if ($productVariant && $variant['price'] > $productVariant->sale_price && $variant['price'] > $productVariant->sell_price) {
                    $errors[] = "Giá nhập của biến thể ID: {$variant['product_variant_id']} cao hơn giá bán ra!";
                    continue;
                }

                ProductStock::create([
                    'stock_id' => $stock->id,
                    'product_id' => $variant['product_id'] ?? null,
                    'product_variant_id' => $variant['product_variant_id'],
                    'quantity' => $variant['quantity'],
                    'price' => $variant['price'],
                ]);

                $productVariant?->increment('stock', $variant['quantity']);
                $totalAmount += $variant['quantity'] * $variant['price'];
            }

            foreach ($validatedData['products'] ?? [] as $productData) {
                $product = Product::find($productData['product_id']);
                if (!$product) {
                    $errors[] = "Không tìm thấy sản phẩm với ID: {$productData['product_id']}";
                    continue;
                }
                if ($productData['price'] > $product->sale_price && $productData['price'] > $product->sell_price) {
                    $errors[] = "Giá nhập của sản phẩm ID: {$productData['product_id']} cao hơn giá bán ra!";
                    continue;
                }

                ProductStock::create([
                    'stock_id' => $stock->id,
                    'product_id' => $productData['product_id'],
                    'product_variant_id' => $productData['product_variant_id'] ?? null,
                    'quantity' => $productData['quantity'],
                    'price' => $productData['price'],
                ]);

                $product->increment('stock', $productData['quantity']);
                $totalAmount += $productData['quantity'] * $productData['price'];
            }

            $stock->update(['total_amount' => $totalAmount]);

            if (!empty($errors)) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Có lỗi xảy ra khi nhập hàng.',
                    'errors' => $errors,
                ], 422);
            }

            DB::commit();
            return response()->json([
                'success' => true,
                'message' => 'Nhập kho thành công!',
                'stock_id' => $stock->id,
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Lỗi hệ thống, vui lòng thử lại.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
