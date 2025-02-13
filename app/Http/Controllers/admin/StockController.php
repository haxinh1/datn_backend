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
            'products' => 'required|array',
            'products.*.id' => 'required|exists:products,id',
            'products.*.price' => 'nullable|numeric|min:0', // Cho phép null nếu có biến thể
            'products.*.quantity' => 'nullable|integer|min:1', // Cho phép null nếu có biến thể
            'products.*.variants' => 'nullable|array',
            'products.*.variants.*.id' => 'required|exists:product_variants,id',
            'products.*.variants.*.price' => 'required|numeric|min:0',
            'products.*.variants.*.quantity' => 'required|integer|min:1',
        ]);

        DB::beginTransaction();
        try {
            $stock = Stock::create([
                'user_id' => $validatedData['user_id'] ?? 1,
                'total_amount' => 0, // Tạm thời, sẽ cập nhật sau
                'status' => 0,
            ]);

            $totalAmount = 0;
            $errors = [];

            foreach ($validatedData['products'] as $productData) {
                $product = Product::find($productData['id']);
                if (!$product) {
                    $errors[] = "Không tìm thấy sản phẩm với ID: {$productData['id']}";
                    continue;
                }

                if (!empty($productData['variants'])) {
                    foreach ($productData['variants'] as $variant) {
                        $productVariant = ProductVariant::find($variant['id']);
                        if (!$productVariant) {
                            $errors[] = "Không tìm thấy biến thể với ID: {$variant['id']}";
                            continue;
                        }
                
                        // Kiểm tra giá sale_price, nếu không có thì dùng sell_price
                        $comparePrice = $productVariant->sale_price ?? $productVariant->sell_price;
                
                        if ($variant['price'] > $comparePrice) {
                            $errors[] = "Giá nhập của biến thể ID: {$variant['id']} cao hơn giá bán ra!";
                            continue;
                        }
                
                        ProductStock::create([
                            'stock_id' => $stock->id,
                            'product_id' => $product->id,
                            'product_variant_id' => $variant['id'],
                            'quantity' => $variant['quantity'],
                            'price' => $variant['price'],
                        ]);
                
                        $productVariant->increment('stock', $variant['quantity']);
                        $totalAmount += $variant['quantity'] * $variant['price'];
                    }
                } else {
                    // Kiểm tra giá sale_price, nếu không có thì dùng sell_price
                    $comparePrice = $product->sale_price ?? $product->sell_price;
                
                    if ($productData['price'] > $comparePrice) {
                        $errors[] = "Giá nhập của sản phẩm ID: {$productData['id']} cao hơn giá bán ra!";
                        continue;
                    }
                
                    ProductStock::create([
                        'stock_id' => $stock->id,
                        'product_id' => $product->id,
                        'quantity' => $productData['quantity'],
                        'price' => $productData['price'],
                    ]);
                
                    $product->increment('stock', $productData['quantity']);
                    $totalAmount += $productData['quantity'] * $productData['price'];
                }
            }

            // Cập nhật tổng tiền nhập kho
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
