<?php

namespace App\Http\Controllers\admin;


use App\Models\CommentImage;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Comment;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class CommentController extends Controller
{

    // lấy ra hết comment
    public function index(Request $request): JsonResponse
    {
        $query = Comment::query()->whereNull("parent_id");

        // Danh sách bộ lọc
        $filters = ['rating', 'status', 'created_at', 'users_id'];

        foreach ($filters as $filter) {
            if ($request->has($filter)) {
                if ($filter === 'created_at') {
                    $query->whereDate($filter, $request->input($filter));
                } else {
                    $query->where($filter, $request->input($filter));
                }
            }
        }

        // Lấy danh sách comment kèm theo replies và images
        $comments = $query
            ->with([
                'replies' => function ($query) {
                    $query->orderBy('created_at', 'asc'); // Sắp xếp replies theo thời gian
                },
                'images' // Lấy danh sách ảnh kèm theo mỗi comment
            ])
            ->orderBy('created_at', 'desc') // Sắp xếp theo thời gian tạo
            ->paginate(10);

        return response()->json($comments);
    }


    public function detail($id): JsonResponse
    {
        $detailComment = Comment::with([
            'replies' => function ($query) {
                $query->orderBy('created_at', 'asc'); // Sắp xếp replies theo thời gian
            },
            'images' // Lấy danh sách ảnh của comment
        ])
            ->find($id);

        if (!$detailComment) {
            return response()->json(['message' => 'Comment not found'], 404);
        }

        return response()->json($detailComment);
    }


    public function updateComment($id, Request $request): JsonResponse
    {
        $request->validate([
            'status' => 'required|integer',
            // 0 === chờ duỵyệt
            // 1 === đã duyệt
            // 2 ===  đã ẩn
        ]);

        $detailComment = Comment::find($id);

        if (!$detailComment) {
            return response()->json(['message' => 'Comment not found'], 404);
        }

        $detailComment->update(['status' => $request->input('status')]);

        return response()->json(['message' => 'Comment updated successfully']);
    }


    // Hàm duyệt hoặc ẩn nhiêu comment
    public function bulkAction(Request $request): JsonResponse
    {
        $request->validate([
            'comment_ids' => 'required|array',
            'action' => 'required|string|in:approve,hide'
        ]);

        $commentIds = $request->input('comment_ids');
        $action = $request->input('action');
        $status = ($action === 'approve') ? 1 : 2;
         // Duyệt là 1, ẩn là 2
        Comment::whereIn('id', $commentIds)->update(['status' => $status]);

        return response()->json(['message' => 'Bulk action performed successfully']);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'products_id' => 'required|exists:products,id',
            'users_id'    => 'required|exists:users,id',
            'comments'    => 'required|string',
            'rating'      => 'nullable|integer|min:1|max:5',
            'parent_id'   => 'nullable|exists:comments,id',
            'status'      => 'nullable|integer|in:0,1', // 0: Ẩn, 1: Hiện
            'images.*'    => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048', // Mỗi ảnh tối đa 2MB
        ]);


        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Tạo comment
        $comment = Comment::create([
            'products_id'  => $request->products_id,
            'users_id'     => $request->users_id,
            'comments'     => $request->comments,
            'rating'       => $request->rating,
            'comment_date' => now(),
            'status'       => $request->status ?? 1,
            'parent_id'    => $request->parent_id,
        ]);

        // Xử lý ảnh nếu có



        if ($request->hasFile('images')) {
            $files = $request->file('images');
            if (!is_array($files)) {
                $files = [$files];
            }

            foreach ($files as $image) {
                $path = $image->store('comments', 'public'); // Lưu ảnh vào storage/app/public/comments
                CommentImage::create([
                    'comment_id' => $comment->id,
                    'image'  => $path,
                ]);
            }
        }

        return response()->json([
            'message' => 'Comment created successfully!',
            'comment' => $comment->load('images'),
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $comment = Comment::find($id);

        if (!$comment) {
            return response()->json(['message' => 'Comment not found!'], 404);
        }

        $validator = Validator::make($request->all(), [
            'comments'  => 'sometimes|string',
            'rating'    => 'sometimes|integer|min:1|max:5',
            'status'    => 'sometimes|integer|in:0,1',
            'images.*'  => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'remove_images' => 'nullable|array', // Danh sách ID ảnh cần xóa
            'remove_images.*' => 'exists:comment_images,id', // Xác nhận ảnh tồn tại
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Cập nhật dữ liệu comment
        $comment->update([
            'comments'  => $request->comments ?? $comment->comments,
            'rating'    => $request->rating ?? $comment->rating,
            'status'    => $request->status ?? $comment->status,
        ]);
        // Xóa ảnh cũ nếu có yêu cầu
        if ($request->has('remove_images')) {
            foreach ($request->remove_images as $imageId) {
                $image = CommentImage::find($imageId);
                if ($image) {
                    Storage::disk('public')->delete($image->image); // Xóa file khỏi storage
                    $image->delete(); // Xóa bản ghi ảnh trong DB
                }
            }
        }
        // Thêm ảnh mới nếu có
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $image) {
                $path = $image->store('comments', 'public'); // Lưu ảnh vào storage/app/public/comments
                CommentImage::create([
                    'comment_id' => $comment->id,
                    'image'  => $path,
                ]);
            }
        }

        return response()->json([
            'message' => 'Comment updated successfully!',
            'comment' => $comment->load('images'),
        ]);
    }

}
