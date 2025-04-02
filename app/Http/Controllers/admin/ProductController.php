<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\products\PostProductRequest;
use App\Models\Attribute;
use App\Models\AttributeValue;
use App\Models\AttributeValueProduct;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductGalleries;
use App\Models\ProductVariant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        try {
            $products = Product::with([
                'categories',
                'attributeValueProduct.attributeValue',
                'variants',
                'variants.attributeValueProductVariants.attributeValue',
            ])
                ->orderByDesc('id')
                ->get();

            return response()->json([
                'success' => true,
                'message' => 'Danh sách sản phẩm!',
                'data' => $products,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi truy xuất sản phẩm!',
            ], 500);
        }
    }
    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $categories = Category::select('id', 'name', 'parent_id')->get();
        $brands = Brand::select('id', 'name')->get();
        $attributes = Attribute::get();


        return response()->json([
            'categories' => $categories,
            'brands' => $brands,
            'attributes' => $attributes,
        ], 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(PostProductRequest $request)
    {
        $datas = $request->only(
            'brand_id',
            'name',
            'slug',
            'views',
            'content',
            'thumbnail',
            'sku',
            'stock',
            'sell_price',
            'sale_price',
            'sale_price_start_at',
            'sale_price_end_at',
            'is_active'
        );

        $datas['sku'] = $datas['sku'] ?? 'PD00' . ((Product::max('id') ?? 0) + 1);
        $datas['slug'] = $datas['slug'] ?? "";
        $datas['stock'] = $datas['stock'] ?? 0;

        try {
            DB::beginTransaction();
            $product = Product::create($datas);

            if (!empty($request->category_id)) {
                $product->categories()->sync($request->category_id);
            }

            if ($request->has('product_images')) {
                $images = collect($request->input('product_images'))
                    ->map(fn($image) => ['product_id' => $product->id, 'image' => $image])
                    ->toArray();
                ProductGalleries::insert($images);
            }

            if ($request->has('attribute_values_id')) {
                $attributes = collect($request->input('attribute_values_id'))
                    ->map(fn($id) => ['product_id' => $product->id, 'attribute_value_id' => $id])
                    ->toArray();
                DB::table('attribute_value_products')->insert($attributes);
            }

            if ($request->has('product_variants')) {
                foreach ($request->input('product_variants') as $variant) {
                    $productVariant = ProductVariant::create([
                        'product_id' => $product->id,
                        'sku' => $variant['sku'] ?? 'VR00' . ((ProductVariant::max('id') ?? 0) + 1),
                        'stock' => $variant['stock'] ?? 0,
                        'sell_price' => $variant['sell_price'],
                        'sale_price' => $variant['sale_price'] ?? null,
                        'sale_price_start_at' => $variant['sale_price_start_at'] ?? null,
                        'sale_price_end_at' => $variant['sale_price_end_at'] ?? null,
                        'thumbnail' => $variant['thumbnail'] ?? null,
                    ]);

                    // Thêm giá trị thuộc tính liên kết với biến thể
                    if (!empty($variant['attribute_values'])) {
                        foreach ($variant['attribute_values'] as $attributeValueId) {
                            DB::table('attribute_value_product_variants')->insert([
                                'product_variant_id' => $productVariant->id,
                                'attribute_value_id' => $attributeValueId,
                            ]);
                        }
                    }
                }
            }

            DB::commit();
            return response()->json([
                'success' => true,
                'message' => 'Tạo sản phẩm và biến thể thành công!',
                'data' => $product,
            ], 201);
        } catch (\Throwable $th) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Lỗi: ' . $th->getMessage(),
            ], 500);
        }
    }


    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        try {
            $product = Product::with([
                'categories',
                'galleries',
                'attributeValueProduct.attributeValue',
                'variants',
                'variants.attributeValueProductVariants.attributeValue',
            ])->where('id', $id)->firstOrFail();


            $stocks = DB::table('product_stocks')
                ->leftJoin('products', 'product_stocks.product_id', '=', 'products.id')
                ->leftJoin('product_variants', 'product_stocks.product_variant_id', '=', 'product_variants.id')
                ->leftJoin('stocks', 'product_stocks.stock_id', '=', 'stocks.id') // Join bảng stocks
                ->select([
                    'product_stocks.id',
                    'products.name as product_name',
                    'products.thumbnail as product_thumbnail',
                    'product_stocks.quantity',
                    'product_stocks.price',
                    'product_variants.id as product_variant_id',
                    'product_variants.sku as variant_sku',
                    'product_variants.thumbnail as variant_image',
                    'product_stocks.created_at'
                ])
                ->where('product_stocks.product_id', $id)
                ->where('stocks.status', 1) // Chỉ lấy sản phẩm có stock đã xác nhận
                ->get();

            $orders = DB::table('order_items')
                ->join('products', 'order_items.product_id', '=', 'products.id')
                ->join('orders', 'order_items.order_id', '=', 'orders.id')
                ->join('order_statuses', 'orders.status_id', '=', 'order_statuses.id') // Join thêm bảng order_statuses
                ->select(
                    'products.name as product_name',
                    'order_items.quantity',
                    'order_statuses.name as status_name',
                    'orders.fullname',
                    'orders.payment_id',
                    'order_items.product_variant_id',
                    'orders.address',
                    'orders.updated_at'
                )
                ->where('order_items.product_id', $id)
                ->orderByDesc('orders.created_at')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $product,
                'stocks' => $stocks,
                'orders' => $orders,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Sản phẩm không tìm thấy hoặc xảy ra lỗi!' . $e->getMessage(),
            ], 404);
        }
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
        $product = Product::findOrFail($id);
        $datas = $request->only(
            'brand_id',
            'name',
            'name_link',
            'slug',
            'views',
            'content',
            'thumbnail',
            'sku',
            'sell_price',
            'sale_price',
            'sale_price_start_at',
            'sale_price_end_at',
            'is_active'
        );

        $datas['stock'] = $datas['stock'] ?? 0;

        try {
            DB::beginTransaction();

            $product->update($datas);

            if ($request->has('category_id')) {
                $product->categories()->sync($request->category_id);
            }

            if ($request->has('product_images')) {
                ProductGalleries::where('product_id', $product->id)->delete();
                foreach ($request->input('product_images') as $image) {
                    if (!empty($image)) {
                        ProductGalleries::create([
                            'product_id' => $product->id,
                            'image' => $image,
                        ]);
                    }
                }
            }

            if ($request->has('attribute_values_id')) {
                $data = [];
                foreach ($request->input('attribute_values_id') as $attributeValueId) {
                    $data[] = [
                        'product_id' => $product->id,
                        'attribute_value_id' => $attributeValueId,
                    ];
                }
                DB::table('attribute_value_products')->insertOrIgnore($data);
            }


            if ($request->has('product_variants')) {
                foreach ($request->input('product_variants') as $variant) {
                    if (!empty($variant['id'])) {
                        $productVariant = ProductVariant::find($variant['id']);
                        if ($productVariant) {
                            $productVariant->update([
                                'sku' => $variant['sku'] ?? $productVariant->sku,
                                'sell_price' => $variant['sell_price'] ?? $productVariant->sell_price,
                                'stock' => $variant['stock'] ?? $productVariant->stock,
                                'sale_price' => $variant['sale_price'] ?? $productVariant->sale_price,
                                'sale_price_start_at' => $variant['sale_price_start_at'] ?? $productVariant->sale_price_start_at,
                                'sale_price_end_at' => $variant['sale_price_end_at'] ?? $productVariant->sale_price_end_at,
                                'thumbnail' => $variant['thumbnail'] ?? $productVariant->thumbnail,
                            ]);
                        }
                    } else {
                        $productVariant = ProductVariant::create([
                            'product_id' => $product->id,
                            'sku' => $variant['sku'] ?? null,
                            'sell_price' => $variant['sell_price'] ?? 0,
                            'stock' => $variant['stock'] ?? 0,
                            'sale_price' => $variant['sale_price'] ?? null,
                            'sale_price_start_at' => $variant['sale_price_start_at'] ?? null,
                            'sale_price_end_at' => $variant['sale_price_end_at'] ?? null,
                            'thumbnail' => $variant['thumbnail'] ?? null,
                        ]);
                    }

                    if (!empty($variant['attribute_values'])) {
                        DB::table('attribute_value_product_variants')
                            ->where('product_variant_id', $productVariant->id)
                            ->delete();

                        foreach ($variant['attribute_values'] as $attributeValueId) {
                            DB::table('attribute_value_product_variants')->insert([
                                'product_variant_id' => $productVariant->id,
                                'attribute_value_id' => $attributeValueId,
                            ]);
                        }
                    }
                }
            }

            DB::commit();
            return response()->json([
                'success' => true,
                'message' => 'Cập nhật sản phẩm thành công!',
                'data' => $product,
            ], 200);
        } catch (\Throwable $th) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Lỗi: ' . $th->getMessage(),
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
    public function active(string $id)
    {
        $product = Product::find($id);
        if (!$product) {
            return response()->json([
                'success' => false,
                'message' => 'Sản phẩm không tìm thấy hoặc xảy ra lỗi!',
            ], 404);
        }
        try {
            if ($product->is_active == 1) {
                $product->update(['is_active' => 0]);
            } else {
                $product->update(['is_active' => 1]);
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
    public function filterProducts(Request $request)
    {
        $filters = $request->query();

        $query = DB::table('products as p')
            ->join('product_variants as pv', 'p.id', '=', 'pv.product_id')
            ->join('attribute_value_product_variants as avpv', 'pv.id', '=', 'avpv.product_variant_id')
            ->join('attribute_values as av', 'avpv.attribute_value_id', '=', 'av.id')
            ->join('attributes as a', 'av.attribute_id', '=', 'a.id')
            ->select(
                'p.id as product_id',
                'p.name',
                'pv.id as variant_id',
                'pv.sku as variant_sku',
                DB::raw("GROUP_CONCAT(av.value ORDER BY av.id SEPARATOR ', ') AS attribute_values")
            )
            ->groupBy('p.id', 'pv.id');

        if (!empty($filters)) {
            $query->where(function ($q) use ($filters) {
                foreach ($filters as $key => $value) {
                    $q->orWhere(function ($sub) use ($key, $value) {
                        $sub->where('a.name', $key)
                            ->where('av.value', $value);
                    });
                }
            });
        }

        $products = $query->get();

        return response()->json($products);
    }
}
