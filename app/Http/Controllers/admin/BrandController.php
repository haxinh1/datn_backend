<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class BrandController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $brands = Brand::orderByDesc('id')->get();
        return response()->json([
            'success' => true,
            'message' => "Đây là danh sách thương hiệu",
            'data' => $brands,
        ]);
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
    public function store(Request $request)
    {
        $data = $request->only(['name', 'slug','logo', 'is_active']);
        $validator = Validator::make($data, [
            'name' => 'required|string|max:255|unique:brands',
            'slug' => 'nullable|string|max:255',
            'logo' => 'nullable|string|max:255',
            'is_active' => 'nullable|boolean',
        ]);
        $data['is_active'] = $data['is_active'] ?? 0;

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi nhập liệu',
                'errors' => $validator->errors(),
            ]);
        }
        try {
            $brands = Brand::create($data);
            return response()->json([
                'success' => true,
                'message' => 'Thêm dữ liệu thành công',
                'data' => $brands,
            ], 201);
        } catch (\Throwable $th) {
            return response()->json([
                'success' => false,
                'message' => $th->getMessage(),
            ]);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        try {
            $brand = Brand::findOrFail($id);
            return response()->json([
                'success' => true,
                'message' => 'Chi tiết dữ liệu',
                'data' => $brand,
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'success' => false,
                'message' => 'Không có dữ liệu phù hợp',
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
        $brand = Brand::find($id);
        if(!$brand){
            return response()->json([
                'success' => false,
                'message' => 'Không có dữ liệu phù hợp',
            ], 404);
        }
        $data = $request->all();
        $validator = Validator::make($data, [
            'name' => 'required|string|max:255|unique:brands,name,'.$id,
            'slug' => 'nullable|string|max:255',
            'is_variant' => 'nullable|boolean',
            'is_active' => 'nullable|boolean',
        ]);
        $data['is_active'] = $data['is_active'] ?? 0;

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi nhập liệu',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $brand->update($data);

            return response()->json([
                'success' => true,
                'message' => 'Cập nhật dữ liệu thành công',
                'data' => $brand,
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi hệ thống: ' . $th->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $brand = Brand::find($id);
        if (!$brand) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy dữ liệu',
            ], 404);
        }
        try {
            $brand->delete();
            return response()->json([
                'success' => true,
                'message' => 'Xóa thành công',
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi hệ thống: ' . $th->getMessage(),
            ], 422);
        }
    }
}
