<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Product;
use App\Models\Order;
use App\Models\OrderReturn;

class SearchController extends Controller
{
    public function searchUsers(Request $request)
    {
        $request->validate([
            'keyword' => 'required|string',
        ]);
        

        $keyword = $request->keyword;

        $users = User::where('role', 'customer')
        ->where(function ($query) use ($keyword) {
            $query->where('fullname', 'LIKE', "%{$keyword}%")
                ->orWhere('email', 'LIKE', "%{$keyword}%")
                ->orWhere('phone_number', 'LIKE', "%{$keyword}%");
        }) 
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

    public function searchOrders(Request $request)
    {
        $validated = $request->validate([
            'keyword' => 'required|string',
        ]);

        $keyword = $validated['keyword'];

        $orders = Order::where('code', 'LIKE', '%' . $keyword . '%')
            ->orwhere('fullname', 'LIKE', '%' . $keyword . '%')
            ->get();

        if ($orders->isEmpty()) {
            return response()->json(['message' => 'Không tìm thấy đơn hàng'], 404);
        }

        return response()->json($orders);
    }

    public function searchOrderReturn(Request $request)
    {
        $validated = $request->validate([
            'keyword' => 'required|string',
        ]);

        $keyword = $validated['keyword'];   
        $orderReturns = OrderReturn::with(['order:id,code,phone_number,fullname,status_id'])->whereHas('order', function ($query) use ($keyword) {
            $query->where('code', 'LIKE', '%' . $keyword . '%');
        })->get();
    
        if ($orderReturns->isEmpty()) {
            return response()->json(['message' => 'Không tìm thấy đơn hàng trả'], 404);
        }
    
        return response()->json($orderReturns);
    }
}
