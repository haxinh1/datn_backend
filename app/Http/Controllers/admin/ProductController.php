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
                'atributeValueProduct.attributeValue',
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
                        'sku' => $variant['sku'] ?? 'VR00' . ((ProductVariant::max('id') ?? 0) + 1),,
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
                'atributeValueProduct.attributeValue',
                'variants',
                'variants.attributeValueProductVariants.attributeValue',
            ])->where('id', $id)->firstOrFail();

            $stocks = DB::table('product_stocks')
            ->leftJoin('products', 'product_stocks.product_id', '=', 'products.id')
            ->leftJoin('product_variants', 'product_stocks.product_variant_id', '=', 'product_variants.id')
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
            ->get();
            return response()->json([
                'success' => true,
                'data' => $product,
                'stocks' =>$stocks
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Sản phẩm không tìm thấy hoặc xảy ra lỗi!',
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

            // Cập nhật thông tin sản phẩm
            $product->update($datas);

            // Cập nhật danh mục sản phẩm
            if ($request->has('category_id')) {
                $product->categories()->sync($request->category_id);
            }

            // Cập nhật hình ảnh sản phẩm
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

            // Cập nhật thuộc tính sản phẩm
            if ($request->has('attribute_values_id')) {
                DB::table('attribute_value_products')->where('product_id', $product->id)->delete();
                foreach ($request->input('attribute_values_id') as $attributeValueId) {
                    DB::table('attribute_value_products')->insert([
                        'product_id' => $product->id,
                        'attribute_value_id' => $attributeValueId,
                    ]);
                }
            }

            // Xử lý biến thể sản phẩm (không xóa hết mà cập nhật hoặc thêm mới)
            if ($request->has('product_variants')) {
                foreach ($request->input('product_variants') as $variant) {
                    // Nếu có `id` => Cập nhật, nếu không có `id` => Tạo mới
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
                        // Nếu không có `id`, tạo mới biến thể
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

                    // Cập nhật thuộc tính cho biến thể
                    if (!empty($variant['attribute_values'])) {
                        // Xóa những thuộc tính cũ nếu có
                        DB::table('attribute_value_product_variants')
                            ->where('product_variant_id', $productVariant->id)
                            ->delete();

                        // Thêm lại các thuộc tính mới
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
}
