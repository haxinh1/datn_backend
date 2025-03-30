<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderReturn;
use Illuminate\Http\Request;
use App\Models\RefundDetail;
use Illuminate\Support\Facades\DB;

class RefundController extends Controller
{
    public function requestRefundByOrder(Request $request, $orderId)
    {
        $request->validate([
            'note' => 'nullable|string',
            'employee_evidence' => 'nullable|string',
            'user_id' => 'required|exists:users,id'
        ]);

        $returns = OrderReturn::where('order_id', $orderId)->get();

        if ($returns->isEmpty()) {
            return response()->json(['message' => 'Không tìm thấy yêu cầu trả hàng cho đơn này'], 404);
        }

        DB::beginTransaction();
        try {
            foreach ($returns as $return) {
                RefundDetail::create([
                    'order_id' => $orderId,
                    'order_return_id' => $return->id,
                    'note' => $request->note,
                    'employee_evidence' => $request->employee_evidence,
                    'status' => 12
                ]);

                $return->update(['status_id' => 12]); // "Yêu cầu hoàn tiền"
            }

            Order::where('id', $orderId)->update(['status_id' => 12]);

            DB::table('order_order_statuses')->insert([
                'order_id' => $orderId,
                'order_status_id' => 12,
                'modified_by' => $request->user_id,
                'note' => 'Yêu cầu hoàn tiền',
                'created_at' => now(),
                'updated_at' => now()
            ]);

            DB::commit();
            return response()->json(['message' => 'Đã gửi yêu cầu hoàn tiền cho đơn hàng #' . $orderId]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Lỗi khi gửi yêu cầu hoàn tiền', 'error' => $e->getMessage()], 500);
        }
    }

    // 2. Admin xác nhận hoàn tiền theo order_id
    public function confirmRefundByOrder(Request $request, $orderId)
    {
        $request->validate([
            'note' => 'nullable|string',
            'employee_evidence' => 'nullable|string',
            'user_id' => 'required|exists:users,id'
        ]);

        $refunds = RefundDetail::where('order_id', $orderId)->get();
        $returns = OrderReturn::where('order_id', $orderId)->get();

        if ($refunds->isEmpty()) {
            return response()->json(['message' => 'Không tìm thấy bản ghi hoàn tiền'], 404);
        }

        DB::beginTransaction();
        try {
            foreach ($refunds as $refund) {
                $refund->update([
                    'status' => 13,
                    'note' => $request->note,
                    'employee_evidence' => $request->employee_evidence
                ]);
            }

            foreach ($returns as $return) {
                $return->update(['status_id' => 13]); // "Hoàn tiền thành công"
            }

            Order::where('id', $orderId)->update(['status_id' => 13]);

            DB::table('order_order_statuses')->insert([
                'order_id' => $orderId,
                'order_status_id' => 13,
                'modified_by' => $request->user_id,
                'note' => 'Hoàn tiền thành công',
                'created_at' => now(),
                'updated_at' => now()
            ]);

            DB::commit();
            return response()->json(['message' => 'Đã xác nhận hoàn tiền thành công cho đơn hàng #' . $orderId]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Lỗi xác nhận hoàn tiền', 'error' => $e->getMessage()], 500);
        }
    }

    // Lấy danh sách tất cả các đơn hoàn tiền, nhóm theo order_id
    public function index(Request $request)
    {
        $orderId = $request->query('order_id'); // Lấy order_id từ query parameter

        // Lấy danh sách hoàn tiền với thông tin liên quan đến order và product
        $query = RefundDetail::with(['order', 'orderReturn.product', 'orderReturn.productVariant']); // Liên kết với bảng order và orderReturn

        if ($orderId) {
            $query->where('order_id', $orderId); // Lọc theo order_id nếu có
        }

        // Lấy tất cả các đơn hoàn tiền, nhóm theo order_id
        $refundDetails = $query->get()
            ->groupBy('order_id') // Nhóm theo order_id
            ->map(function ($refunds, $orderId) {
                $firstRefund = $refunds->first(); // Lấy bản ghi hoàn tiền đầu tiên trong nhóm
                $order = $firstRefund->order; // Lấy thông tin đơn hàng

                // Trả về thông tin của đơn hàng và các sản phẩm
                return [
                    'order_id' => $orderId,
                    'note' => $firstRefund->note, // Chỉ lấy lý do hoàn tiền từ bản ghi đầu tiên
                    'employee_evidence' => $firstRefund->employee_evidence, // Video chứng minh từ bản ghi đầu tiên
                    'order' => $order ? $order->toArray() : null, // Chuyển toàn bộ order thành mảng
                    'order_returns' => $refunds->map(function ($refund) {
                        $orderReturn = $refund->orderReturn; // Lấy thông tin đơn hoàn trả từ quan hệ orderReturn
                        $product = $orderReturn->product; // Lấy sản phẩm
                        $variant = $orderReturn->productVariant; // Lấy variant sản phẩm

                        // Trả về thông tin chi tiết của từng đơn hoàn trả
                        return [
                            'order_return_id' => $orderReturn->id, // Trả về order_return_id
                            'reason' => $orderReturn->reason, // Lý do hoàn trả
                            'employee_evidence' => $orderReturn->employee_evidence, // Video chứng minh từ bảng order_return
                            'product' => [
                                'product_id' => $product->id,
                                'name' => $product->name,
                                'thumbnail' => $variant ? $variant->thumbnail : $product->thumbnail,
                                'sell_price' => $variant ? $variant->sell_price : $orderReturn->price,
                                'product_variant_id' => $orderReturn->product_variant_id,
                                'quantity' => $orderReturn->quantity_returned,
                                'attributes' => $variant ? $variant->attributeValues->map(function ($attr) {
                                    return [
                                        'attribute_name' => $attr->value,
                                        'attribute_id' => $attr->attribute_id,
                                    ];
                                }) : [],
                            ]
                        ];
                    })->values(), // Trả về danh sách các đơn hoàn trả trong mảng
                ];
            })->values(); // Trả về tất cả các đơn hoàn tiền đã nhóm theo order_id

        return response()->json([
            'refund_details' => $refundDetails, // Trả về tất cả dữ liệu đã xử lý
        ], 200);
    }

    public function show($orderId)
    {
        // Kiểm tra nếu không có order_id thì trả về lỗi
        if (!$orderId) {
            return response()->json(['message' => 'order_id không được cung cấp.'], 400);
        }

        // Lấy danh sách hoàn tiền với thông tin liên quan đến order và product cho một order_id cụ thể
        $query = RefundDetail::with(['order', 'orderReturn.product', 'orderReturn.productVariant']); // Liên kết với bảng order và orderReturn

        $query->where('order_id', $orderId); // Lọc theo order_id

        // Lấy các dữ liệu hoàn tiền cho order_id cụ thể
        $refundDetails = $query->get()
            ->groupBy('order_id') // Nhóm theo order_id
            ->map(function ($refunds, $orderId) {
                $firstRefund = $refunds->first(); // Lấy bản ghi hoàn tiền đầu tiên trong nhóm
                $order = $firstRefund->order; // Lấy thông tin đơn hàng

                // Trả về thông tin của đơn hàng và các sản phẩm
                return [
                    'order_id' => $orderId,
                    'note' => $firstRefund->note, // Lý do hoàn tiền
                    'employee_evidence' => $firstRefund->employee_evidence, // Video chứng minh từ bản ghi đầu tiên
                    'order' => $order ? $order->toArray() : null, // Chuyển toàn bộ order thành mảng
                    'order_returns' => $refunds->map(function ($refund) {
                        $orderReturn = $refund->orderReturn; // Lấy thông tin đơn hoàn trả từ quan hệ orderReturn
                        $product = $orderReturn->product; // Lấy sản phẩm
                        $variant = $orderReturn->productVariant; // Lấy variant sản phẩm

                        // Trả về thông tin chi tiết của từng đơn hoàn trả
                        return [
                            'order_return_id' => $orderReturn->id, // Trả về order_return_id
                            'reason' => $orderReturn->reason, // Lý do hoàn trả từ bảng order_return
                            'employee_evidence' => $orderReturn->employee_evidence, // Video chứng minh từ bảng order_return
                            'product' => [
                                'product_id' => $product->id,
                                'name' => $product->name,
                                'thumbnail' => $variant ? $variant->thumbnail : $product->thumbnail,
                                'sell_price' => $variant ? $variant->sell_price : $orderReturn->price,
                                'product_variant_id' => $orderReturn->product_variant_id,
                                'quantity' => $orderReturn->quantity_returned,
                                'attributes' => $variant ? $variant->attributeValues->map(function ($attr) {
                                    return [
                                        'attribute_name' => $attr->value,
                                        'attribute_id' => $attr->attribute_id,
                                    ];
                                }) : [],
                            ]
                        ];
                    })->values(), // Trả về danh sách các đơn hoàn trả trong mảng
                ];
            })->values(); // Trả về tất cả các đơn hoàn tiền đã nhóm theo order_id

        // Trả về kết quả
        return response()->json([
            'refund_details' => $refundDetails, // Trả về tất cả dữ liệu đã xử lý
        ], 200);
    }
}
