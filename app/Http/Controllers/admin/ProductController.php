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
        // Lấy các dữ liệu cần thiết từ request
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

        if (empty($datas['sku'])) {
            $latestId = Product::max('id') ?? 0;
            $datas['sku'] = 'PD00' . ($latestId + 1);
        }

        if (empty($datas['name_link'])) {
            $datas['name_link'] = "";
        }

        if (empty($datas['slug'])) {
            $datas['slug'] = "";
        }

        try {
            DB::beginTransaction();

            // Tạo sản phẩm
            $product = Product::create($datas);

            // Đồng bộ danh mục
            if ($request->has('category_id')) {
                $product->categories()->sync($request->category_id);
            }

            // Thêm hình ảnh sản phẩm
            if ($request->has('product_images')) {
                foreach ($request->input('product_images') as $image) {
                    if (!empty($image)) {
                        ProductGalleries::create([
                            'product_id' => $product->id,
                            'image' => $image,
                        ]);
                    }
                }
            }
            // Thêm giá trị thuộc tính vào bảng attribute_value_products
            if ($request->has('attribute_values_id')) {
                foreach ($request->input('attribute_values_id') as $attributeValueId) {
                    DB::table('attribute_value_products')->insert([
                        'product_id' => $product->id,
                        'attribute_value_id' => $attributeValueId,
                    ]);
                }
            }

            // Thêm biến thể sản phẩm
            if ($request->has('product_variants')) {
                foreach ($request->input('product_variants') as $variant) {
                    $productVariant = ProductVariant::create([
                        'product_id' => $product->id,
                        'sku' => $variant['sku'] ?? null,
                        'price' => $variant['price'],
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

            return response()->json([
                'success' => true,
                'data' => $product,
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
        // Lấy sản phẩm cần cập nhật
        $product = Product::find($id);
        if (!$product) {
            return response()->json([
                'success' => false,
                'message' => 'Sản phẩm không tồn tại!',
            ], 404);
        }

        // Lấy các dữ liệu cần thiết từ request
        $datas = $request->only(
            'brand_id',
            'name',
            'name_link',
            'slug',
            'views',
            'content',
            'thumbnail',
            'sku',
            'price',
            'sale_price',
            'sale_price_start_at',
            'sale_price_end_at',
            'is_active'
        );

        if (empty($datas['name_link'])) {
            $datas['name_link'] = "";
        }
        if (empty($datas['slug'])) {
            $datas['slug'] = "";
        }

        try {
            DB::beginTransaction();

            // Cập nhật sản phẩm với dữ liệu đã thu thập
            $product->update($datas);

            // Xử lý các thuộc tính của sản phẩm
            if ($request->has('attribute_id') && $request->has('attribute_values')) {
                $attributeIds = $request->input('attribute_id'); // name="attribute_id[]"
                $attributeValues = $request->input('attribute_values'); // name="attribute_values[attribute_id][]"
                $attributeValueIds = [];
                foreach ($attributeIds as $attributeId) {
                    if (isset($attributeValues[$attributeId])) {
                        foreach ($attributeValues[$attributeId] as $value) {
                            if (!empty($value)) {
                                $attributeValue = AttributeValue::where('value', $value)
                                    ->where('attribute_id', $attributeId)
                                    ->first();

                                if (!$attributeValue) {
                                    $attributeValue = AttributeValue::create([
                                        'attribute_id' => $attributeId,
                                        'value' => $value,
                                    ]);
                                }
                                $attributeValueIds[] = $attributeValue->id;

                                AttributeValueProduct::updateOrCreate(
                                    ['product_id' => $product->id, 'attribute_value_id' => $attributeValue->id]
                                );
                            }
                        }
                    }
                }
            }

            // Xử lý các biến thể sản phẩm
            if ($request->has('variants')) {
                foreach ($request->input('variants') as $index => $variant) {
                    ProductVariant::updateOrCreate(
                        ['id' => $variant['id'] ?? null], // Cập nhật nếu có ID, tạo mới nếu không
                        [
                            'product_id' => $product->id,
                            'sku' => $variant['sku'] ?? ('SKU000' . (ProductVariant::max('id') + 1)),
                            'price' => $variant['price'],
                            'sale_price' => $variant['sale_price'] ?? null,
                            'sale_price_start_at' => $variant['sale_price_start_at'] ?? null,
                            'sale_price_end_at' => $variant['sale_price_end_at'] ?? null,
                            'thumbnail' => $variant['thumbnail'] ?? null,
                        ]
                    );
                }
            }

            // Đồng bộ danh mục sản phẩm
            if ($request->has('category_id')) {
                $product->categories()->sync($request->category_id);
            }

            // Xử lý hình ảnh sản phẩm
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
