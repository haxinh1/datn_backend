<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\stocks\StoreStockRequest;
use App\Http\Requests\stocks\UpdateStockRequest;
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
            'users.fullname as created_by',
            'stocks.created_at as ngaytao',
            'stocks.updated_at as ngaycapnhap'
        ])
            ->join('users', 'stocks.created_by', '=', 'users.id')
            ->with('productStocks.product', 'productStocks.productVariant')
            ->get();

        $formattedStocks = $stocks->map(function ($stock) {
            $products = [];

            foreach ($stock->productStocks as $productStock) {
                $productId = $productStock->product->id ?? null;

                if (!$productId) continue;

                if (!isset($products[$productId])) {
                    $products[$productId] = [
                        'id' => $productId,
                        'name' => $productStock->product->name ?? null,
                        'thumbnail' => $productStock->product->thumbnail ?? null,
                        'variants' => []
                    ];
                }

                if ($productStock->productVariant) {
                    $products[$productId]['variants'][] = [
                        'id' => $productStock->productVariant->id ?? null,
                        'price' => $productStock->price,
                        'quantity' => $productStock->quantity,
                        'thumbnail' => $productStock->productVariant->thumbnail ?? null
                    ];
                } else {
                    $products[$productId]['price'] = $productStock->price;
                    $products[$productId]['quantity'] = $productStock->quantity;
                }
            }

            return [
                'id' => $stock->id,
                'status' => $stock->status,
                'total_amount' => $stock->total_amount,
                'created_by' => $stock->created_by,
                'ngaytao' => $stock->ngaytao,
                'ngaycapnhap' => $stock->ngaycapnhap,
                'products' => array_values($products)
            ];
        });

        return response()->json([
            'success' => true,
            'message' => 'Danh sách nhập kho',
            'data' => $formattedStocks
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
    public function store(StoreStockRequest $request)
    {
        $validatedData = $request->all();
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
                'products.id as product_id',
                'products.name as product_name',
                'product_variants.id as variant_id',
                'product_variants.sku as variant_sku',
                'product_variants.thumbnail as variant_image'
            ])
            ->where('product_stocks.stock_id', $id)
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Chi tiết nhập kho',
            'data' => $products

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
    public function update(UpdateStockRequest $request, $id)
    {
        $validatedData = $request->all();
        DB::beginTransaction();

        try {
            $stock = Stock::find($id);
            if (!$stock) {
                return response()->json([
                    'success' => false,
                    'message' => 'Không tìm thấy thông tin nhập kho!',
                ], 404);
            }

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

                        // Kiểm tra giá nhập không được cao hơn giá bán
                        if ($variant['price'] > 0 && $variant['sell_price'] !== null && $variant['price'] >= $variant['sell_price']) {
                            $errors[] = "Giá nhập ({$variant['price']}) của biến thể ID {$variant['id']} không được cao hơn giá bán ({$variant['sell_price']})!";
                            continue;
                        }

                        // Cập nhật stock quantity (chỉ cập nhật chứ không cộng dồn)
                        $productStock = ProductStock::where([
                            'stock_id' => $stock->id,
                            'product_id' => $product->id,
                            'product_variant_id' => $variant['id']
                        ])->first();

                        if ($productStock) {
                            $productStock->update([
                                'quantity' => $variant['quantity'],
                                'price' => $variant['price'],
                                'sell_price' => $variant['sell_price'] ?? null,
                                'sale_price' => $variant['sale_price'] ?? null,
                            ]);
                        } else {
                            ProductStock::create([
                                'stock_id' => $stock->id,
                                'product_id' => $product->id,
                                'product_variant_id' => $variant['id'],
                                'quantity' => $variant['quantity'],
                                'price' => $variant['price'],
                                'sell_price' => $variant['sell_price'] ?? null,
                                'sale_price' => $variant['sale_price'] ?? null,
                            ]);
                        }

                        // **Chỉ cập nhật bảng product_variant nếu status == 1**
                        if ($validatedData['status'] == 1) {
                            $productVariant->stock += $variant['quantity'];
                            $productVariant->sell_price = $variant['sell_price'] ?? $productVariant->sell_price;
                            $productVariant->sale_price = $variant['sale_price'] ?? $productVariant->sale_price;
                            $productVariant->save();
                        }

                        $totalAmount += $variant['quantity'] * $variant['price'];
                    }
                } else {
                    // Kiểm tra giá nhập không được cao hơn giá bán
                    if ($productData['price'] > 0 && $productData['sell_price'] !== null && $productData['price'] >= $productData['sell_price']) {
                        $errors[] = "Giá nhập ({$productData['price']}) của sản phẩm ID {$productData['id']} không được cao hơn giá bán ({$productData['sell_price']})!";
                        continue;
                    }

                    $productStock = ProductStock::where([
                        'stock_id' => $stock->id,
                        'product_id' => $product->id
                    ])->whereNull('product_variant_id')->first();

                    if ($productStock) {
                        $productStock->update([
                            'quantity' => $productData['quantity'],
                            'price' => $productData['price'],
                            'sell_price' => $productData['sell_price'] ?? null,
                            'sale_price' => $productData['sale_price'] ?? null,
                        ]);
                    } else {
                        ProductStock::create([
                            'stock_id' => $stock->id,
                            'product_id' => $product->id,
                            'quantity' => $productData['quantity'],
                            'price' => $productData['price'],
                            'sell_price' => $productData['sell_price'] ?? null,
                            'sale_price' => $productData['sale_price'] ?? null,
                        ]);
                    }

                    // **Chỉ cập nhật bảng product nếu status == 1**
                    if ($validatedData['status'] == 1) {
                        $product->stock += $productData['quantity'];
                        $product->sell_price = $productData['sell_price'] ?? $product->sell_price;
                        $product->sale_price = $productData['sale_price'] ?? $product->sale_price;
                        $product->save();
                    }

                    $totalAmount += $productData['quantity'] * $productData['price'];
                }
            }

            // Nếu có lỗi, rollback và trả về response
            if (!empty($errors)) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Có lỗi xảy ra khi cập nhật nhập kho.',
                    'errors' => $errors,
                ], 422);
            }

            // Cập nhật total_amount, status, reason
            $stock->update([
                'total_amount' => $totalAmount,
                'status' => $validatedData['status'],
                'reason' => $validatedData['reason'] ?? $stock->reason,
            ]);

            DB::commit();
            return response()->json([
                'success' => true,
                'message' => 'Cập nhật nhập kho thành công!',
                'stock_id' => $stock->id,
            ], 200);
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
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
