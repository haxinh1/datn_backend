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
            'sell_price',
            'sale_price',
            'sale_price_start_at',
            'sale_price_end_at',
            'is_active'
        );
    
        try {
            DB::beginTransaction();
    
            $product = Product::create($datas);
            if ($product && $request->has('category_id')) {
                $product->categories()->sync($request->category_id);
            }
            if ($request->has('attribute_values')) {
                foreach ($request->input('attribute_values') as $attributeId => $values) {
                    foreach ((array)$values as $value) {  
                        if (!empty($value)) {  
                            $attributeValue = AttributeValue::firstOrCreate([
                                'attribute_id' => $attributeId,  
                                'value' => $value,
                            ]);
                            AttributeValueProduct::create([
                                'product_id' => $product->id,
                                'attribute_value_id' => $attributeValue->id,
                            ]);
                        }
                    }
                }
            }
    
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
