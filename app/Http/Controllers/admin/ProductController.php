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
        $attributes = Attribute::select('id', 'name')->get();

        return response()->json([
            'categories' => $categories,
            'brands' => $brands,
            'attributes' => $attributes
        ], 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(PostProductRequest $request)
    {
        // Lấy các dữ liệu cần thiết từ request
        $datas = $request->only(
            'brand_id',       // name="brand_id"
            'name',               // name="name"
            'name_link',          // name="name_link"
            'slug',               // name="slug"
            'views',              // name="views"
            'content',            // name="content"
            'thumbnail',          // name="thumbnail"
            'sku',                // name="sku"
            'price',              // name="price"
            'sale_price',         // name="sale_price"
            'sale_price_start_at', // name="sale_price_start_at"
            'sale_price_end_at',  // name="sale_price_end_at"
            'is_active'           // name="is_active"
        );
        if (empty($datas['sku'])) {
            $latestId = Product::max('id') ?? 0;
            $datas['sku'] = 'PD000' . ($latestId + 1); // Tạo SKU: SKU000 + (ID mới nhất + 1)
        }
        if (empty($datas['name_link'])) {
            $datas['name_link'] = "";
        }
        if (empty($datas['slug'])) {
            $datas['slug'] = "";
        }

        try {
            DB::beginTransaction();
            // Tạo sản phẩm với dữ liệu đã thu thập
            $product = Product::create($datas);

            // Xử lý các thuộc tính của sản phẩm
            if ($request->has('attribute_id') && $request->has('attribute_values')) {
                $attributeIds = $request->input('attribute_id'); // name="attribute_id[]"
                $attributeValues = $request->input('attribute_values'); // name="attribute_values[attribute_id][]"
                $attributeValueIds = []; // Lưu các ID giá trị thuộc tính
                foreach ($attributeIds as $attributeId) {
                    if (isset($attributeValues[$attributeId])) {
                        // Xử lý giá trị thuộc tính cho từng thuộc tính
                        foreach ($attributeValues[$attributeId] as $value) {
                            if (!empty($value)) {
                                // Kiểm tra giá trị thuộc tính đã tồn tại chưa
                                $attributeValue = AttributeValue::where('value', $value)
                                    ->where('attribute_id', $attributeId)
                                    ->first();

                                // Nếu không tìm thấy thì tạo mới
                                if (!$attributeValue) {
                                    $attributeValue = AttributeValue::create([
                                        'attribute_id' => $attributeId,
                                        'value' => $value,
                                    ]);
                                }
                                $attributeValueIds[] = $attributeValue->id;
                                // Liên kết giá trị thuộc tính với sản phẩm
                                AttributeValueProduct::firstOrCreate([
                                    'product_id' => $product->id,
                                    'attribute_value_id' => $attributeValue->id,
                                ]);
                            }
                        }
                    }
                }
            }

            // Xử lý các biến thể sản phẩm
            $productVariantIds = []; // Lưu các ID biến thể sản phẩm

            if ($request->has('variants')) {
                foreach ($request->input('variants') as $index => $variant) {
                    // Tạo biến thể sản phẩm với dữ liệu được cung cấp
                    $latestId = ProductVariant::max('id') ?? 0;
                    $productVariant = ProductVariant::create([
                        'product_id' => $product->id,
                        'sku' => 'SKU000' . ($latestId + 1),// Tự động tạo SKU cho từng biến thể
                        'price' => $variant['price'],   // name="variants[<index>][price]": Giá biến thể
                        'sale_price' => $variant['sale_price'] ?? null, // name="variants[<index>][sale_price]"
                        'sale_price_start_at' => $variant['sale_price_start_at'] ?? null, // name="variants[<index>][sale_price_start_at]"
                        'sale_price_end_at' => $variant['sale_price_end_at'] ?? null, // name="variants[<index>][sale_price_end_at]"
                        'thumbnail' => $variant['thumbnail'] ?? null, // name="variants[<index>][thumbnail]"
                    ]);

                    $productVariantIds[] = $productVariant->id; // Lưu ID biến thể
                }
            }

            // Liên kết thuộc tính với biến thể nếu cả hai tồn tại
            if (!empty($attributeValueIds) && !empty($productVariantIds)) {
                foreach ($productVariantIds as $index => $productVariantId) {
                    if (isset($attributeValueIds[$index])) {
                        DB::table('attribute_value_product_variants')->insert([
                            'product_variant_id' => $productVariantId,
                            'attribute_value_id' => $attributeValueIds[$index],
                        ]);
                    }
                }
            }

            // Đồng bộ danh mục sản phẩm
            // name="category_id[]"
            if ($request->has('category_id')) {
                $product->categories()->sync($request->category_id);
            }

            // Xử lý hình ảnh sản phẩm
            // name="product_images[]"
            if ($request->has('product_images')) {
                foreach ($request->input('product_images') as $image) {
                    if (!empty($image)) {
                        // Tạo bản ghi hình ảnh sản phẩm
                        ProductGalleries::create([
                            'product_id' => $product->id,
                            'image' => $image,
                        ]);
                    }
                }
            }
            DB::commit();
            // Trả về phản hồi thành công
            return response()->json([
                'success' => true,
                'message' => 'Tạo sản phẩm và biến thể thành công!',
                'data' => $product, // Dữ liệu sản phẩm vừa được tạo
            ], 201);
        } catch (\Throwable $th) {
            DB::rollBack();
            // Trả về phản hồi lỗi
            return response()->json([
                'success' => false,
                'message' => 'Lỗi: ' . $th->getMessage(), // Thông báo lỗi chi tiết
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
