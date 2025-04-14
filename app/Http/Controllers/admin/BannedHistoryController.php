<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\BannedHistory;
use Illuminate\Support\Facades\Validator;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class BannedHistoryController extends Controller
{
    // Lấy danh sách lịch sử banned
    public function index()
    {
        $bannedHistories = BannedHistory::with([
            'user', 
            'bannedBy' ])->get();
        return response()->json($bannedHistories);
    }


    public function store(Request $request)
    {
     
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id',
            'reason' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        
        $user = User::find($request->user_id);
        if ($user->status === 'banned') {
            return response()->json(['message' => 'User is already banned'], 400);
        }

        // Tạo bản ghi lịch sử banned
        $bannedHistory = BannedHistory::create([
            'user_id' => $request->user_id,
            'banned_by' => auth()->id(),
            'reason' => $request->reason,
            'banned_at' => now(),
            'unbanned_at' => null,
        ]);

       
        $user->update(['status' => 'banned']);

        return response()->json($bannedHistory, 201);
    }


    public function unban(Request $request, $id)
    {

        $bannedHistory = BannedHistory::findOrFail($id);

    
        if (!$bannedHistory->isActive()) {
            return response()->json(['message' => 'Ban already lifted'], 400);
        }
        $bannedHistory->update(['unbanned_at' => now()]);
        $user = $bannedHistory->user;
        $user->update(['status' => 'active']);
    
        return response()->json($bannedHistory);
    }
}
