<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\Tag;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class TagController extends Controller
{
    
    public function index()
    {
        $tags = Tag::orderByDesc('id')->get();
        return response()->json([
            'success' => true,
            'message' => "Danh sách tag",
            'data' => $tags,
        ]);
    }

    
    public function store(Request $request)
    {
        $data = $request->only(['name', 'slug']);
        $validator = Validator::make($data, [
            'name' => 'required|string|max:255|unique:tags',
            'slug' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi nhập liệu',
                'errors' => $validator->errors(),
            ]);
        }

        // Tự động tạo slug nếu không được cung cấp
        $data['slug'] = $data['slug'] ?? Str::slug($data['name']);

        try {
            $tag = Tag::create($data);
            return response()->json([
                'success' => true,
                'message' => 'Thêm tag thành công',
                'data' => $tag,
            ], 201);
        } catch (\Throwable $th) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi hệ thống: ' . $th->getMessage(),
            ], 500);
        }
    }

    
    public function show(string $id)
    {
        try {
            $tag = Tag::findOrFail($id);
            return response()->json([
                'success' => true,
                'message' => 'Chi tiết tag',
                'data' => $tag,
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'success' => false,
                'message' => 'Không có dữ liệu phù hợp',
            ], 404);
        }
    }

    
    public function update(Request $request, string $id)
    {
        $tag = Tag::find($id);
        if (!$tag) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy dữ liệu',
            ], 404);
        }

        $data = $request->only(['name', 'slug']);
        $validator = Validator::make($data, [
            'name' => 'required|string|max:255|unique:tags,name,' . $id,
            'slug' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi nhập liệu',
                'errors' => $validator->errors(),
            ], 422);
        }

        // Tự động tạo slug nếu không được nhập
        $data['slug'] = $data['slug'] ?? Str::slug($data['name']);

        try {
            $tag->update($data);
            return response()->json([
                'success' => true,
                'message' => 'Cập nhật tag thành công',
                'data' => $tag,
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi hệ thống: ' . $th->getMessage(),
            ], 500);
        }
    }

   
    public function destroy(string $id)
    {
        $tag = Tag::find($id);
        if (!$tag) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy dữ liệu',
            ], 404);
        }

        try {
            $tag->delete();
            return response()->json([
                'success' => true,
                'message' => 'Xóa tag thành công',
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi hệ thống: ' . $th->getMessage(),
            ], 500);
        }
    }
}
