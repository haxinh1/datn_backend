<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Http\Requests\StoreCategoryRequest;
use App\Http\Requests\UpdateCategoryRequest;
use Illuminate\Http\Request;



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
    public function update(UpdateCategoryRequest $request, Category $category)
    {
        //
        try {
            $category->name = $request->name;
            $category->slug = $request->slug;
            $category->ordinal = $request->ordinal;
            $category->parent_id = $request->parent_id;
            $category->is_active = $request->is_active;
            $category->save();
            return response()->json($category);
        } catch (\Throwable $throwable) {
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


}
