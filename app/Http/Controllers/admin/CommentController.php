<?php

namespace App\Http\Controllers\admin;


use App\Models\CommentImage;
use App\Models\Order;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Comment;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class CommentController extends Controller
{

    // lấy ra hết comment
    public function index(Request $request): JsonResponse
    {
        $query = Comment::query()->whereNull("parent_id");

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

        $comments = $query
            ->with([
                'user',  // 🟢 Thêm thông tin user của comment
                'replies' => function ($query) {
                    $query->with('user')->orderBy('created_at', 'asc'); // 🟢 Lấy user của replies
                },
                'images'
            ])
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return response()->json($comments);
    }


    public function detail($id): JsonResponse
    {
        // Có thể phân biiệt đựược role user khi lấy cả user ra

        $detailComment = Comment::with([
            'user', // 🟢 Thêm thông tin user
            'replies' => function ($query) {
                $query->with('user')->orderBy('created_at', 'asc'); // 🟢 Lấy user của replies
            },
            'images'
        ])->find($id);

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
        $user = Auth::guard('sanctum')->user();
        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
        $userId = $user->id;

        $validator = Validator::make($request->all(), [
            'products_id' => 'required|exists:products,id',
            'comments'    => 'required|string',
            'rating'      => 'nullable|integer|min:1|max:5',
            'parent_id'   => 'nullable|exists:comments,id',
            'status'      => 'nullable|integer|in:0,1',
            'images'      => 'nullable|array',
        ]);

        $productId = $request->input('products_id');

        if ($user && $user->role === "customer") {
            $existingComment = Comment::where('products_id', $productId)
                ->where('users_id', $userId)
                ->whereNull('parent_id')
                ->exists();

            if ($existingComment) {
                return response()->json(['error' => 'Bạn chỉ được phép bình luận 1 lần trên sản phẩm này'], 403);
            }

            if (!Order::hasPurchasedProduct($userId, $productId)) {
                return response()->json(['error' => 'Bạn chưa mua sản phẩm này!'], 403);
            }
        }

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $comment = Comment::create([
            'products_id'  => $productId,
            'users_id'     => $userId,
            'comments'     => $request->comments,
            'rating'       => $request->rating,
            'comment_date' => now(),
            'status'       => $request->status ?? 1,
            'parent_id'    => $request->parent_id,
        ]);

        if ($request->filled('images')) {
            foreach ($request->images as $imageUrl) {
                CommentImage::create([
                    'comment_id' => $comment->id,
                    'image'      => $imageUrl,
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

        $user = Auth::guard('sanctum')->user();
        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        if ($user->id !== $comment->users_id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validator = Validator::make($request->all(), [
            'comments' => 'sometimes|string',
            'rating'   => 'sometimes|integer|min:1|max:5',
            'status'   => 'sometimes|integer|in:0,1',
            'images'   => 'nullable|array',
            'images.*' => 'nullable|url',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $comment->update([
            'comments' => $request->comments ?? $comment->comments,
            'rating'   => $request->rating ?? $comment->rating,
            'status'   => $request->status ?? $comment->status,
        ]);

        // Xử lý ảnh
        if ($request->has('images')) {
            $newImages = $request->images;
            $oldImages = $comment->images->pluck('image')->toArray();

            if (empty($newImages)) {
                // Nếu mảng ảnh rỗng: XÓA HẾT
                CommentImage::where('comment_id', $comment->id)->delete();
            } elseif (count($newImages) === 1) {
                // Nếu chỉ còn 1 ảnh: GIỮ 1, XÓA HẾT CÁI KHÁC
                CommentImage::where('comment_id', $comment->id)
                    ->where('image', '!=', $newImages[0])
                    ->delete();

                // Nếu ảnh đó chưa có thì thêm
                if (!in_array($newImages[0], $oldImages)) {
                    CommentImage::create([
                        'comment_id' => $comment->id,
                        'image'      => $newImages[0],
                    ]);
                }
            } else {
                // Trường hợp nhiều ảnh: xử lý thông thường
                foreach ($oldImages as $oldImage) {
                    if (!in_array($oldImage, $newImages)) {
                        CommentImage::where('comment_id', $comment->id)
                            ->where('image', $oldImage)
                            ->delete();
                    }
                }

                foreach ($newImages as $newImage) {
                    if (!in_array($newImage, $oldImages)) {
                        CommentImage::create([
                            'comment_id' => $comment->id,
                            'image'      => $newImage,
                        ]);
                    }
                }
            }
        }

        return response()->json([
            'message' => 'Comment updated successfully!',
            'comment' => $comment->load('images'),
        ]);
    }





    // Lấy ra comment theo product
    public function getCommentsByProduct(Request $request, $productId): JsonResponse
    {
        $query = Comment::where('products_id', $productId)->whereNull("parent_id");

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

        $comments = $query
            ->with([
                'user', // 🟢 Thêm thông tin user
                'replies' => function ($query) {
                    $query->with('user')->orderBy('created_at', 'asc'); // 🟢 Lấy user của replies
                },
                'images'
            ])
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return response()->json($comments);
    }



}
