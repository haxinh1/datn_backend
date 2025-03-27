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
            ->exists();

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
            ->first();

        if (!$orderItem) {
            return response()->json(['message' => 'Sản phẩm không tồn tại trong đơn hàng'], 404);
        }

        // Kiểm tra số lượng trả có hợp lệ không
        if ($request->quantity > $orderItem->quantity) {
            return response()->json(['message' => 'Số lượng trả vượt quá số lượng sản phẩm trong đơn hàng'], 400);
        }

        // Cập nhật trạng thái đơn hàng trong bảng orders
        DB::table('orders')
            ->where('id', $orderId)
            ->update(['status_id' => 9]); // Trạng thái 'Chờ xử lý trả hàng'

        // Lưu thông tin trả hàng vào bảng order_returns
        $orderReturnId = DB::table('order_returns')->insertGetId([
            'order_id' => $orderId,
            'product_id' => $request->product_id,
            'product_variant_id' => $request->product_variant_id,
            'quantity_returned' => $request->quantity,
            'reason' => $request->reason,
            'employee_evidence' => $request->employee_evidence,
            'status_id' => 9, // Chờ xử lý trả hàng
            'price' => $orderItem->sell_price,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Lưu lịch sử trạng thái trả hàng
        $userId = $request->input('user_id');
        DB::table('order_order_statuses')->insert([
            'order_id' => $orderId,
            'order_status_id' => 9, // Trạng thái 'Chờ xử lý trả hàng'
            'modified_by' => $userId,
            'note' => "Trả lại $request->quantity sản phẩm ID $request->product_id",
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json([
            'message' => 'Trả hàng thành công!',
            'order_return' => OrderReturn::find($orderReturnId),
        ], 200);

        // Lấy thông tin chi tiết từ các bảng liên quan
        $orderReturn = OrderReturn::with(['order', 'product', 'productVariant'])->find($orderReturnId);
        $orderItem = $orderItem;
        $user = $request->user();
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
    // Admin xử lý chấp nhận hoặc từ chối yêu cầu trả hàng
    public function updateStatus(Request $request, $returnId)
    {
        $request->validate([
            'status_id' => 'required|in:9,10,11', // 9: Chờ xử lý trả hàng, 10: Chấp nhận trả hàng, 11: Từ chối trả hàng
            'note' => 'nullable|string'
        ]);

        $orderReturn = OrderReturn::findOrFail($returnId);
        $order = Order::findOrFail($orderReturn->order_id);

        DB::beginTransaction();
        try {
            // Cập nhật trạng thái order_returns
            $orderReturn->update(['status_id' => $request->status_id]);

            // Nếu admin chấp nhận trả hàng
            if ($request->status_id == 10) {
                // Trừ quantity trong order_items
                $orderItemQuery = OrderItem::where('order_id', $orderReturn->order_id)
                    ->where('product_id', $orderReturn->product_id);

                if ($orderReturn->product_variant_id) {
                    $orderItemQuery->where('product_variant_id', $orderReturn->product_variant_id);
                }

                $orderItemQuery->decrement('quantity', $orderReturn->quantity_returned);
                // Cập nhật trạng thái đơn hàng khi chấp nhận trả hàng
                $order->update(['status_id' => 10]); // Chấp nhận trả hàng
                // Cập nhật trạng thái trả hàng trong bảng order_returns
                $orderReturn->update(['status_id' => 10]);
            }

            // Cập nhật trạng thái đơn hàng nếu trạng thái là từ chối trả hàng (11)
            if ($request->status_id == 11) {
                $order->update(['status_id' => 11]); // Đơn hàng sẽ có trạng thái "Từ chối trả hàng"
                // Cập nhật trạng thái trả hàng trong bảng order_returns
                $orderReturn->update(['status_id' => 11]);
            }

            $userId = $request->input('user_id');
            // Lưu lịch sử trạng thái (lưu luôn lịch sử cho cả trường hợp chấp nhận hoặc từ chối trả hàng)
            OrderOrderStatus::create([
                'order_id' => $order->id,
                'order_status_id' => $request->status_id,
                'modified_by' => $userId,
                'note' => $request->note ?? ($request->status_id == 10 ? 'Admin chấp nhận trả hàng' : 'Admin từ chối trả hàng'),
                'created_at' => now(),
                'updated_at' => now()
            ]);

            DB::commit();

            return response()->json(['message' => 'Cập nhật trạng thái trả hàng thành công!']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Lỗi cập nhật trả hàng', 'error' => $e->getMessage()], 500);
        }
    }
}
