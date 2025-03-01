<?php

namespace App\Http\Controllers\admin;


use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Comment;
use Illuminate\Http\JsonResponse;

class CommentController extends Controller
{

    // lấy ra hết comment
    public function index(Request $request): JsonResponse
    {
        $comments = Comment::query()
            ->when($request->input('rating'), function ($query, $rating) {  // Lọc theo rating tối đa 5
                return $query->where('rating', $rating);
            })
            ->when($request->input('status'), function ($query, $status) {
                return $query->where('status', $status);
            })
            ->when($request->input('created_at'), function ($query, $created_at) {
                return $query->whereDate('created_at', $created_at);
            })
            ->when($request->input('users_id'), function ($query, $users_id) {
                return $query->whereDate('users_id', $users_id);
            })
            ->when($request->input('users_id'), function ($query, $users_id) {
                return $query->whereDate('users_id', $users_id);
            })
            ->orderBy('created_at', 'desc') // Sắp xếp theo thời gian tạo
            ->paginate(10);

        return response()->json($comments);
    }

    public function store(Request $request): JsonResponse
    {
        // Validate dữ liệu đầu vào
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'user_id'    => 'required|exists:users,id',
            'comments'   => 'required|string',
            'rating'     => 'required|integer|min:1|max:5', // Rating bắt buộc từ 1-5 sao
        ]);

        // Kiểm tra user đã mua sản phẩm chưa
//        $hasPurchased = Order::where('user_id', $validated['user_id'])
//            ->where('product_id', $validated['product_id'])
//            ->where('status', 'completed') // Chỉ xét đơn hàng đã hoàn thành
//            ->exists();

//        if (!$hasPurchased) {
//            return response()->json([
//                'message' => 'Bạn chưa mua sản phẩm này, không thể bình luận.'
//            ], 403);
//        }

        // Nếu đã mua, tạo bình luận mới
        $comment = Comment::create([
            'products_id' => $validated['product_id'],
            'users_id'    => $validated['user_id'],
            'comments'    => $validated['comments'],
            'rating'      => $validated['rating'],
            'comment_date' => now(),
            'status'      => 0, // Bình luận cần duyệt trước khi hiển thị
        ]);

        return response()->json([
            'message' => 'Bình luận của bạn đã được gửi và đang chờ xét duyệt.',
            'comment' => $comment
        ], 201);
    }



    public function detail($id): JsonResponse
    {
        $detailComment = Comment::query()->find($id);
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
}
