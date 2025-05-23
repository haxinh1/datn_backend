<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\stocks\StoreStockRequest;
use App\Http\Requests\stocks\UpdateStockRequest;
use App\Imports\ProductStockImport;
use App\Models\Product;
use App\Models\ProductStock;
use App\Models\ProductVariant;
use App\Models\Stock;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class StockController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $createdBy = $request->input('user_id'); // Đúng cú pháp

        $stocksQuery = Stock::select([
            'stocks.id',
            'stocks.status',
            'stocks.total_amount',
            'users.fullname as created_by',
            'stocks.created_at as ngaytao',
            'stocks.updated_at as ngaycapnhap'
        ])
            ->join('users', 'stocks.created_by', '=', 'users.id')
            ->orderByDesc('ngaytao')
            ->with('productStocks.product', 'productStocks.productVariant');

        if ($createdBy) {
            $stocksQuery->where('stocks.created_by', $createdBy);
        }

        $stocks = $stocksQuery->get();

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
    function store(StoreStockRequest $request)
    {
        $validatedData = $request->all();
        DB::beginTransaction();
        try {
            $user = Auth::guard('sanctum')->user();
            $stock = Stock::create([
                'total_amount' => 0,
                'status' =>  $user->role == "admin" ? 1 : 0,
                'reason' => $validatedData['reason'] ?? null,
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

                        $sellPrice = $variant['sell_price'] ?? null;
                        $salePrice = $variant['sale_price'] ?? null;

                        if ($variant['price'] > 0 && $user->role == "admin" && $sellPrice !== null && $variant['price'] >= $sellPrice) {
                            $errors[] = "Giá nhập ({$variant['price']}) của biến thể ID {$variant['id']} không được cao hơn giá bán ({$sellPrice})!";
                            continue;
                        }

                        if ($user->role == "admin" && $salePrice !== null && $sellPrice !== null && $salePrice > $sellPrice) {
                            $errors[] = "Giá khuyến mại ({$salePrice}) của biến thể ID {$variant['id']} không được cao hơn giá bán ({$sellPrice})!";
                            continue;
                        }

                        ProductStock::create([
                            'stock_id' => $stock->id,
                            'product_id' => $product->id,
                            'product_variant_id' => $variant['id'],
                            'quantity' => $variant['quantity'],
                            'price' => $variant['price'],
                            'sell_price' => $user->role == "admin" ? ($sellPrice > 0 ? $sellPrice : $productVariant->sell_price) : null,
                            'sale_price' => $user->role == "admin" ? ($salePrice > 0 ? $salePrice : $productVariant->sale_price) : null,
                        ]);

                        if ($user->role == "admin") {
                            $productVariant->stock += $variant['quantity'];
                            $productVariant->sell_price = $sellPrice > 0 ? $sellPrice : $productVariant->sell_price;
                            $productVariant->sale_price = $salePrice > 0 ? $salePrice : $productVariant->sale_price;

                            // Chỉ cập nhật thời gian khuyến mãi nếu là admin
                            if (isset($variant['sale_price_start_at'])) {
                                $productVariant->sale_price_start_at = $variant['sale_price_start_at'];
                            }

                            if (isset($variant['sale_price_end_at'])) {
                                $productVariant->sale_price_end_at = $variant['sale_price_end_at'];
                            }

                            $productVariant->save();
                        }

                        $totalAmount += $variant['quantity'] * $variant['price'];
                    }
                } else {
                    $sellPrice = $productData['sell_price'] ?? null;
                    $salePrice = $productData['sale_price'] ?? null;

                    if ($productData['price'] > 0 && $user->role == "admin" && $sellPrice !== null && $productData['price'] >= $sellPrice) {
                        $errors[] = "Giá nhập ({$productData['price']}) của sản phẩm ID {$productData['id']} không được cao hơn giá bán ({$sellPrice})!";
                        continue;
                    }

                    if ($user->role == "admin" && $salePrice !== null && $sellPrice !== null && $salePrice > $sellPrice) {
                        $errors[] = "Giá khuyến mại ({$salePrice}) của sản phẩm ID {$productData['id']} không được cao hơn giá bán ({$sellPrice})!";
                        continue;
                    }

                    ProductStock::create([
                        'stock_id' => $stock->id,
                        'product_id' => $product->id,
                        'quantity' => $productData['quantity'],
                        'price' => $productData['price'],
                        'sell_price' => $user->role == "admin" ? ($sellPrice > 0 ? $sellPrice : $product->sell_price) : null,
                        'sale_price' => $user->role == "admin" ? ($salePrice > 0 ? $salePrice : $product->sale_price) : null,
                    ]);

                    if ($user->role == "admin") {
                        $product->stock += $productData['quantity'];
                        $product->sell_price = $sellPrice > 0 ? $sellPrice : $product->sell_price;
                        $product->sale_price = $salePrice > 0 ? $salePrice : $product->sale_price;

                        // Chỉ cập nhật thời gian khuyến mãi nếu là admin
                        if (isset($productData['sale_price_start_at'])) {
                            $product->sale_price_start_at = $productData['sale_price_start_at'];
                        }

                        if (isset($productData['sale_price_end_at'])) {
                            $product->sale_price_end_at = $productData['sale_price_end_at'];
                        }

                        $product->save();
                    }

                    $totalAmount += $productData['quantity'] * $productData['price'];
                }
            }

            if (!empty($errors)) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Có lỗi xảy ra khi nhập hàng.',
                    'errors' => $errors,
                ], 422);
            }

            $stock->update([
                'total_amount' => $totalAmount,
                'reason' => $validatedData['reason'] ?? $stock->reason,
            ]);

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
                'products.total_sales as total_sales',
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
    
            $user = Auth::guard('sanctum')->user();
            $isAdmin = $user->role == "admin";
            $totalAmount = 0;
    
            foreach ($validatedData['products'] as $productData) {
                $product = Product::find($productData['id']);
                if (!$product) {
                    DB::rollBack();
                    return response()->json([
                        'success' => false,
                        'message' => "Không tìm thấy sản phẩm với ID: {$productData['id']}",
                    ], 422);
                }
    
                if (!empty($productData['variants'])) {
                    foreach ($productData['variants'] as $variant) {
                        $productVariant = ProductVariant::find($variant['id']);
                        if (!$productVariant) {
                            DB::rollBack();
                            return response()->json([
                                'success' => false,
                                'message' => "Không tìm thấy biến thể với ID: {$variant['id']}",
                            ], 422);
                        }
    
                        if ($variant['price'] > 0 && isset($variant['sell_price']) && $variant['price'] >= $variant['sell_price']) {
                            DB::rollBack();
                            return response()->json([
                                'success' => false,
                                'message' => "Giá nhập ({$variant['price']}) của biến thể ID {$variant['id']} không được cao hơn giá bán ({$variant['sell_price']})!",
                            ], 422);
                        }
                        
                        if ($isAdmin && isset($variant['sale_price']) && isset($variant['sell_price']) && $variant['sale_price'] > $variant['sell_price']) {
                            DB::rollBack();
                            return response()->json([
                                'success' => false,
                                'message' => "Giá khuyến mại ({$variant['sale_price']}) của biến thể ID {$variant['id']} không được cao hơn giá bán ({$variant['sell_price']})!",
                            ], 422);
                        }
    
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
    
                        if ($validatedData['status'] == 1 && $isAdmin) {
                            $productVariant->stock += $variant['quantity'];
                            $productVariant->sell_price = $variant['sell_price'] ?? $productVariant->sell_price;
                            $productVariant->sale_price = $variant['sale_price'] ?? $productVariant->sale_price;
                            
                            if (isset($variant['sale_price_start_at'])) {
                                $productVariant->sale_price_start_at = $variant['sale_price_start_at'];
                            }
                            
                            if (isset($variant['sale_price_end_at'])) {
                                $productVariant->sale_price_end_at = $variant['sale_price_end_at'];
                            }
                            
                            $productVariant->save();
                        }
    
                        $totalAmount += $variant['quantity'] * $variant['price'];
                    }
                } else {
                    if ($productData['price'] > 0 && isset($productData['sell_price']) && $productData['price'] >= $productData['sell_price']) {
                        DB::rollBack();
                        return response()->json([
                            'success' => false,
                            'message' => "Giá nhập ({$productData['price']}) của sản phẩm ID {$productData['id']} không được cao hơn giá bán ({$productData['sell_price']})!",
                        ], 422);
                    }
                    
                    if ($isAdmin && isset($productData['sale_price']) && isset($productData['sell_price']) && $productData['sale_price'] > $productData['sell_price']) {
                        DB::rollBack();
                        return response()->json([
                            'success' => false,
                            'message' => "Giá khuyến mại ({$productData['sale_price']}) của sản phẩm ID {$productData['id']} không được cao hơn giá bán ({$productData['sell_price']})!",
                        ], 422);
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
    
                    if ($validatedData['status'] == 1 && $isAdmin) {
                        $product->stock += $productData['quantity'];
                        $product->sell_price = $productData['sell_price'] ?? $product->sell_price;
                        $product->sale_price = $productData['sale_price'] ?? $product->sale_price;
                        
                        if (isset($productData['sale_price_start_at'])) {
                            $product->sale_price_start_at = $productData['sale_price_start_at'];
                        }
                        
                        if (isset($productData['sale_price_end_at'])) {
                            $product->sale_price_end_at = $productData['sale_price_end_at'];
                        }
                        
                        $product->save();
                    }
    
                    $totalAmount += $productData['quantity'] * $productData['price'];
                }
            }
    
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
    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls',
        ]);

        try {
            DB::beginTransaction();

            $import = new ProductStockImport();
            Excel::import($import, $request->file('file'));

            if (!empty($import->errors)) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Có lỗi xảy ra trong quá trình nhập kho.',
                    'errors' => $import->errors,
                ], 422);
            }

            DB::commit();
            return response()->json([
                'success' => true,
                'message' => 'Nhập kho thành công!',
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
}
