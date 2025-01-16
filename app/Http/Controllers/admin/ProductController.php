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
        //
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
            'sell_price',         // name="sell_price"
            'sale_price',         // name="sale_price"
            'sale_price_start_at', // name="sale_price_start_at"
            'sale_price_end_at',  // name="sale_price_end_at"
            'is_active'           // name="is_active"
        );
        if (empty($datas['sku'])) {
            $latestId = Product::max('id') ?? 0;
            $datas['sku'] = 'PD000' . ($latestId + 1); // Tạo SKU: SKU000 + (ID mới nhất + 1)
        }
        try {
            DB::beginTransaction();

            // Tạo sản phẩm với dữ liệu đã thu thập
            $product = Product::create($datas);

            // Xử lý các giá trị thuộc tính của sản phẩm 
            // name="attribute_values[]"
            if ($request->has('attribute_values')) {
                $attributeValueIds = []; // Lưu các ID giá trị thuộc tính

                foreach ($request->input('attribute_values') as $attributeId => $values) {
                    foreach ((array) $values as $value) {
                        if (!empty($value)) {
                            // Tìm hoặc tạo giá trị thuộc tính mới
                            $attributeValue = AttributeValue::firstOrCreate([
                                'attribute_id' => $attributeId, 
                                'value' => $value,             // Giá trị thuộc tính
                            ]);

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
