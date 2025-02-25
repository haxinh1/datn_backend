<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Product;

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
    public function searchProducts(Request $request)
    {
        $request->validate([
            'keyword' => 'required|string',
        ]);

        $keyword = $request->keyword;
        $products = Product::where('name', 'LIKE', "%{$keyword}%")
            ->with([
                'categories',
                'atributeValueProduct.attributeValue',
                'variants',
                'variants.attributeValueProductVariants.attributeValue',
            ])
            ->orderByDesc('id')
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Danh sách sản phẩm tìm kiếm!',
            'data' => $products,
        ], 200);
    }

}
