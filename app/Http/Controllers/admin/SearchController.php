<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;

class SearchController extends Controller
{
    public function searchUsers(Request $request)
    {
        $request->validate([
            'keyword' => 'required|string',
        ]);

        $keyword = $request->keyword;
        $users = User::where('fullname', 'LIKE', "%{$keyword}%")
            ->orWhere('email', 'LIKE', "%{$keyword}%")
            ->orWhere('phone_number', 'LIKE', "%{$keyword}%")
            ->get();

        return response()->json($users, 200);
    }
}
