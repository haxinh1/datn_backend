<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Http\Requests\StoreCategoryRequest;
use App\Http\Requests\UpdateCategoryRequest;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;


class CategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $categories = Category::with('children')->where("parent_id", "=", null)->get();
        return response()->json($categories);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(StoreCategoryRequest $request)
    {

        try {
            $category = new Category();
            $category->name = $request->name;
            $category->slug = $request->slug;
            $category->ordinal = $request->ordinal;
            $category->parent_id = $request->parent_id;
            if ($request->hasFile('thumbnail')) {
                $path = $request->thumbnail->store('categories', 'public');
                $category->thumbnail = $path;
            }
            $category->save();
            return response()->json($category);

        } catch (\Throwable $throwable) {
            return response()->json(['message' => $throwable->getMessage()], 500);
        }
    }


    /**
     * Display the specified resource.
     */
    public function show(Category $category)
    {
        return response()->json($category->load('children'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Category $category)
    {
        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255'],
            'parent_id' => ['nullable', 'integer', 'exists:categories,id'],
            'ordinal' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
            'thumbnail'    => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048', // Mỗi ảnh tối đa 2MB
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            DB::beginTransaction();
            $category->name = $request->name;
            $category->slug = $request->slug;
            $category->ordinal = $request->ordinal;
            $category->parent_id = $request->parent_id;
            $category->is_active = $request->is_active;
            if ($request->hasFile('thumbnail')) {
                $path = $request->thumbnail->store('categories', 'public');
                $category->thumbnail = $path;
            }
            $category->save();
            return response()->json($category);
        } catch (\Throwable $throwable) {
            DB::rollBack();
            return response()->json(['message' => $throwable->getMessage()], 400);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Category $category)
    {

        try {
            $category->delete();
            return $this->json(['message' => 'Xóa thành công'], 200);
        } catch (\Throwable $throwable) {
            return response()->json(['message' => $throwable->getMessage()], 400);
        }

    }

    public function updateStatus(Category $category, Request $request)
    {

        $category->is_active = $request->is_active;
        $category->save();
        return response()->json($category, 200); // Đảm bảo mã trạng thái là 200 (OK)

    }

    public function getProductByCategory($id)
    {
        try {
            $products = Product::with([
                'atributeValueProduct.attributeValue', // Lấy thông tin giá trị thuộc tính của sản phẩm
                'variants', // Lấy các biến thể của sản phẩm
                'variants.attributeValueProductVariants.attributeValue', // Lấy thông tin giá trị thuộc tính của các biến thể
            ])
                ->whereHas('categories', function ($query) use ($id) {
                    $query->where('category_id', $id); // Lọc sản phẩm theo category_id
                })
                ->orderByDesc('id')
                ->get();

            return response()->json([
                'success' => true,
                'message' => 'Danh sách sản phẩm theo danh mục!',
                'data' => $products,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi truy xuất sản phẩm theo danh mục!',
            ], 500);
        }
    }

}
