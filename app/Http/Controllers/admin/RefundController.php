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
        $orderId = $request->query('order_id'); 

        // Lấy danh sách hoàn tiền với thông tin liên quan đến order và product
        $query = RefundDetail::with(['order', 'orderReturn.product', 'orderReturn.productVariant']); 

        if ($orderId) {
            $query->where('order_id', $orderId); 
        }

        // Lấy tất cả các đơn hoàn tiền, nhóm theo order_id
        $refundDetails = $query->orderBy('created_at', 'desc')
            ->get()
            ->groupBy('order_id') 
            ->map(function ($refunds, $orderId) {
                $firstRefund = $refunds->first(); 
                $order = $firstRefund->order; 

                // Trả về thông tin của đơn hàng và các sản phẩm
                return [
                    'order_id' => $orderId,
                    'note' => $firstRefund->note, 
                    'employee_evidence' => $firstRefund->employee_evidence, 
                    'order' => $order ? $order->toArray() : null, 
                    'order_returns' => $refunds->map(function ($refund) {
                        $orderReturn = $refund->orderReturn; 
                        $product = $orderReturn->product; 
                        $variant = $orderReturn->productVariant; 

                        // Trả về thông tin chi tiết của từng đơn hoàn trả
                        return [
                            'order_return_id' => $orderReturn->id,
                            'reason' => $orderReturn->reason, 
                            'employee_evidence' => $orderReturn->employee_evidence, 
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
                    })->values(), 
                ];
            })->values(); 

        return response()->json([
            'refund_details' => $refundDetails, 
        ], 200);
    }

    public function show($orderId)
    {
        // Kiểm tra nếu không có order_id thì trả về lỗi
        if (!$orderId) {
            return response()->json(['message' => 'order_id không được cung cấp.'], 400);
        }

        // Lấy danh sách hoàn tiền với thông tin liên quan đến order và product cho một order_id cụ thể
        $query = RefundDetail::with(['order', 'orderReturn.product', 'orderReturn.productVariant']); 

        $query->where('order_id', $orderId); 

        // Lấy các dữ liệu hoàn tiền cho order_id cụ thể
        $refundDetails = $query->orderBy('created_at', 'desc')
            ->get()
            ->groupBy('order_id') 
            ->map(function ($refunds, $orderId) {
                $firstRefund = $refunds->first(); 
                $order = $firstRefund->order; 

                // Trả về thông tin của đơn hàng và các sản phẩm
                return [
                    'order_id' => $orderId,
                    'note' => $firstRefund->note, 
                    'employee_evidence' => $firstRefund->employee_evidence, 
                    'order' => $order ? $order->toArray() : null, 
                    'order_returns' => $refunds->map(function ($refund) {
                        $orderReturn = $refund->orderReturn; 
                        $product = $orderReturn->product; 
                        $variant = $orderReturn->productVariant;

                        // Trả về thông tin chi tiết của từng đơn hoàn trả
                        return [
                            'order_return_id' => $orderReturn->id, 
                            'reason' => $orderReturn->reason, 
                            'employee_evidence' => $orderReturn->employee_evidence, 
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
                    })->values(), 
                ];
            })->values();

        // Trả về kết quả
        return response()->json([
            'refund_details' => $refundDetails, 
        ], 200);
    }
}
