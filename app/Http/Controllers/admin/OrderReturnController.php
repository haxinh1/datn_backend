<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderReturn;
use App\Models\OrderOrderStatus;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderReturnController extends Controller
{
    /**
     * Lưu thông tin trả hàng vào bảng order_returns
     */
    public function store(Request $request, $orderId)
    {
        // Xác nhận dữ liệu đầu vào
        $request->validate([
            'product_id' => 'required|integer|exists:products,id',
            'product_variant_id' => 'nullable|integer|exists:product_variants,id',
            'quantity' => 'required|integer|min:1',
            'reason' => 'required|string|max:255',
            'employee_evidence' => 'nullable|string',
        ]);

        // Kiểm tra xem đã có bản ghi trả hàng cho sản phẩm này trong bảng order_returns chưa
        $existingReturn = DB::table('order_returns')
            ->where('order_id', $orderId)
            ->where('product_id', $request->product_id)
            ->where(function ($query) use ($request) {
                if ($request->filled('product_variant_id')) {
                    $query->where('product_variant_id', $request->product_variant_id);
                }
            })
            ->exists(); // Dùng exists() để kiểm tra sự tồn tại

        if ($existingReturn) {
            return response()->json(['message' => 'Sản phẩm này đã được trả hàng.'], 400);
        }

        // Truy vấn thông tin đơn hàng từ bảng order_items
        $orderItem = DB::table('order_items')
            ->where('order_id', $orderId)
            ->where('product_id', $request->product_id)
            ->when($request->filled('product_variant_id'), function ($query) use ($request) {
                $query->where('product_variant_id', $request->product_variant_id);
            })
            ->first(); // Lấy bản ghi đầu tiên

        if (!$orderItem) {
            return response()->json(['message' => 'Sản phẩm không tồn tại trong đơn hàng'], 404);
        }

        // Kiểm tra số lượng trả có hợp lệ không
        if ($request->quantity > $orderItem->quantity) {
            return response()->json(['message' => 'Số lượng trả vượt quá số lượng sản phẩm trong đơn hàng'], 400);
        }

        // Trừ số lượng trong bảng order_items
        // DB::table('order_items')
        //     ->where('order_id', $orderId)
        //     ->where('product_id', $request->product_id)
        //     ->when($request->filled('product_variant_id'), function ($query) use ($request) {
        //         $query->where('product_variant_id', $request->product_variant_id);
        //     })
        //     ->decrement('quantity', $request->quantity);
        
        // Cập nhật trạng thái đơn hàng
        DB::table('orders')
            ->where('id', $orderId)
            ->update(['status_id' => 9]); // Trạng thái 'Chờ xử lý trả hàng'

        // Lấy giá từ order_item
        $price = $orderItem->sell_price;
        // Lưu thông tin trả hàng vào bảng order_returns
        $orderReturnId = DB::table('order_returns')->insertGetId([
            'order_id' => $orderId,
            'product_id' => $request->product_id,
            'product_variant_id' => $request->product_variant_id,
            'quantity_returned' => $request->quantity,
            'reason' => $request->reason,
            'employee_evidence' => $request->employee_evidence,
            'status' => 'Chờ xử lý trả hàng',
            'price' => $price, // Lưu giá vào bảng order_returns
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Lưu lịch sử trạng thái trả hàng
        $user = $request->user();
        DB::table('order_order_statuses')->insert([
            'order_id' => $orderId,
            'order_status_id' => 9, // Trạng thái 'Chờ xử lý trả hàng'
            'modified_by' => $user ? $user->id : null,
            'note' => "Trả lại $request->quantity sản phẩm ID $request->product_id",
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Lấy thông tin chi tiết từ các bảng liên quan
        $orderReturn = OrderReturn::with(['order', 'product', 'productVariant'])->find($orderReturnId);
        $order = Order::with('orderItems')->find($orderId);
        $orderItem = $orderItem;
        $orderOrderStatus = OrderOrderStatus::where('order_id', $orderId)
            ->latest()
            ->first();

        return response()->json([
            'message' => 'Trả hàng thành công!',
            'order_return' => $orderReturn,
            'order' => $order,
            'order_item' => $orderItem,
            'history' => $orderOrderStatus
        ], 200);
    }



    /**
     * Lấy danh sách các đơn hàng trả lại
     */
    public function index()
    {
        $orderReturns = OrderReturn::with(['order', 'product', 'productVariant'])->get();

        return response()->json([
            'order_returns' => $orderReturns
        ], 200);
    }

    /**
     * Lấy thông tin trả hàng theo ID
     */
    public function show($id)
    {
        $orderReturn = OrderReturn::with(['order', 'product', 'productVariant'])->find($id);

        if (!$orderReturn) {
            return response()->json(['message' => 'Không tìm thấy thông tin trả hàng'], 404);
        }

        return response()->json([
            'order_return' => $orderReturn
        ], 200);
    }
}
