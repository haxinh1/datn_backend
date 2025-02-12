<?php

namespace Modules\Admin\App\Http\Controllers;


use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Comment;
use Illuminate\Http\JsonResponse;

class CommentController extends Controller
{
    public function listComment(Request $request): JsonResponse
    {
        $query = Comment::join('users', 'users.id', '=', 'comments.users_id')
            ->join('products', 'products.id', '=', 'comments.products_id')
            ->select(
                'comments.id',
                'comments.products_id',
                'users.user_name',
                'products.name as product_name',
                'comments.comments',
                'comments.rating',
                'comments.comment_date',
                'comments.status'
            );

        if ($request->has('rating_filter') && $request->get('rating_filter') != '') {
            $query->where('comments.rating', $request->get('rating_filter'));
        }

        if ($request->has('status_filter') && $request->get('status_filter') != '') {
            $query->where('comments.status', $request->get('status_filter'));
        }

        $listComment = $query->orderBy('comments.comment_date', 'desc')->paginate(10);

        return response()->json($listComment);
    }

    public function editComment($id): JsonResponse
    {
        $detailComment = Comment::join('users', 'users.id', '=', 'comments.users_id')
            ->join('products', 'products.id', '=', 'comments.products_id')
            ->select(
                'comments.id',
                'users.user_name',
                'products.name as product_name',
                'comments.comments',
                'comments.rating',
                'comments.comment_date',
                'comments.status'
            )->find($id);

        if (!$detailComment) {
            return response()->json(['message' => 'Comment not found'], 404);
        }

        return response()->json($detailComment);
    }

    public function updateComment($id, Request $request): JsonResponse
    {
        $request->validate([
            'status' => 'required|integer',
        ]);

        $detailComment = Comment::find($id);

        if (!$detailComment) {
            return response()->json(['message' => 'Comment not found'], 404);
        }

        $detailComment->update(['status' => $request->input('status')]);

        return response()->json(['message' => 'Comment updated successfully']);
    }

    public function bulkAction(Request $request): JsonResponse
    {
        $request->validate([
            'comment_ids' => 'required|array',
            'action' => 'required|string|in:approve,hide'
        ]);

        $commentIds = $request->input('comment_ids');
        $action = $request->input('action');
        $status = ($action === 'approve') ? 2 : 3;

        Comment::whereIn('id', $commentIds)->update(['status' => $status]);

        return response()->json(['message' => 'Bulk action performed successfully']);
    }
}
