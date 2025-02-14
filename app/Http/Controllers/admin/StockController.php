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
        $stocks = Stock::select([
            'stocks.id',
            'stocks.status',
            'stocks.total_amount',
            'users.fullname',
            'stocks.created_at as ngaytao',
            'stocks.updated_at as ngaycapnhap'
        ])
            ->join('users', 'stocks.created_by', '=', 'users.id')
            ->get();
        return response()->json([
            'success' => true,
            'message' => 'Danh sách nhập kho',
            'data' => $stocks
        ], 200);
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
            'products.*.price' => 'nullable|numeric|min:0',
            'products.*.quantity' => 'nullable|integer|min:1',
            'products.*.variants' => 'nullable|array',
            'products.*.variants.*.id' => 'required|exists:product_variants,id',
            'products.*.variants.*.price' => 'required|numeric|min:0',
            'products.*.variants.*.quantity' => 'required|integer|min:1',
        ]);

        DB::beginTransaction();
        try {
            $stock = Stock::create([
                'total_amount' => 0,
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
                        if ($variant['price'] > $productVariant->sell_price) {
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
                    if ($productData['price'] > $product->sell_price) {
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
        $stock = Stock::find($id);
        if (!$stock) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy phiếu nhập kho'
            ], 404);
        }
        $products = DB::table('product_stocks')
            ->leftJoin('products', 'product_stocks.product_id', '=', 'products.id')
            ->leftJoin('product_variants', 'product_stocks.product_variant_id', '=', 'product_variants.id')
            ->select([
                'product_stocks.id',
                'product_stocks.quantity',
                'product_stocks.price',
                'products.name as product_name',
                'product_variants.sku as variant_sku',
                'product_variants.thumbnail as variant_image'
            ])
            ->where('product_stocks.stock_id', $id)
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Chi tiết nhập kho',
            'data' => [
                'stock' => $stock,
                'products' => $products
            ]
        ], 200);
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
