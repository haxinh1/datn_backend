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
    public function index(Request $request)
    {
        $orderId = $request->query('order_id'); // Lấy order_id từ query parameter

        $query = OrderReturn::with(['order', 'product', 'productVariant.attributeValues']);

        if ($orderId) {
            $query->where('order_id', $orderId);
        }

        $orderReturns = $query->orderBy('created_at', 'desc')
            ->get()
            ->groupBy('order_id')
            ->map(function ($returns, $orderId) {
                $firstReturn = $returns->first();
                $order = $firstReturn->order;
                return [
                    'order_id' => $orderId,
                    'reason' => $returns->first()->reason,
                    'employee_evidence' => $returns->first()->employee_evidence,
                    'order' => $order ? $order->toArray() : null,
                    'products' => $returns->map(function ($return) {
                        $product = $return->product;
                        $variant = $return->productVariant;

                        return [
                            'product_id' => $product->id,
                            'name' => $product->name,
                            'thumbnail' => $variant?->thumbnail ?? $product->thumbnail,
                            'sell_price' => $variant?->sell_price ?? $return->price,
                            'product_variant_id' => $return->product_variant_id,
                            'quantity' => $return->quantity_returned,
                            'attributes' => $variant ? $variant->attributeValues->map(function ($attr) {
                                return [
                                    'attribute_name' => $attr->value,
                                    'attribute_id' => $attr->attribute_id,
                                ];
                            }) : [],
                        ];
                    })->values(),
                ];
            })->values();

        return response()->json([
            'order_returns' => $orderReturns,
        ], 200);
    }


    /**
     * Lấy thông tin trả hàng theo ID
     */
    public function show($orderId)
    {
        // Lấy thông tin của một order_id cụ thể
        $orderReturns = OrderReturn::with(['order', 'product', 'productVariant.attributeValues'])
            ->where('order_id', $orderId)
            ->orderBy('created_at', 'desc')
            ->get()
            ->groupBy('order_id')
            ->map(function ($returns, $orderId) {
                $firstReturn = $returns->first();
                $order = $firstReturn->order; // Lấy thông tin đơn hàng

                return [
                    'order_id' => $orderId,
                    'reason' => $firstReturn->reason,
                    'employee_evidence' => $firstReturn->employee_evidence,
                    'order' => $order ? $order->toArray() : null, // Chuyển toàn bộ order thành mảng
                    'products' => $returns->map(function ($return) {
                        $product = $return->product;
                        $variant = $return->productVariant;

                        return [
                            'product_id' => $product->id,
                            'name' => $product->name,
                            'thumbnail' => $variant?->thumbnail ?? $product->thumbnail,
                            'sell_price' => $variant?->sell_price ?? $return->price,
                            'product_variant_id' => $return->product_variant_id,
                            'quantity' => $return->quantity_returned,
                            'attributes' => $variant ? $variant->attributeValues->map(function ($attr) {
                                return [
                                    'attribute_name' => $attr->value,
                                    'attribute_id' => $attr->attribute_id,
                                ];
                            }) : [],
                        ];
                    })->values(),
                ];
            })->first();

        if (!$orderReturns) {
            return response()->json([
                'message' => 'Không tìm thấy đơn hàng trả hàng',
            ], 404);
        }

        return response()->json([
            'order_return' => $orderReturns,
        ], 200);
    }

    /**
     * Lưu thông tin trả hàng vào bảng order_returns
     */
    public function store(Request $request, $orderId)
    {
        // Xác thực dữ liệu đầu vào
        $request->validate([
            'user_id' => 'required|integer|exists:users,id',
            'reason' => 'required|string|max:255',
            'employee_evidence' => 'nullable|string',
            'products' => 'required|array|min:1',
            'products.*.product_id' => 'required|integer|exists:products,id',
            'products.*.product_variant_id' => 'nullable|integer|exists:product_variants,id',
            'products.*.quantity' => 'required|integer|min:1',
            'bank_account_number' => 'nullable|string|max:50',
            'bank_name' => 'nullable|string|max:100',
            'bank_qr' => 'nullable|string',
        ]);

        $userId = $request->input('user_id');
        $reason = $request->input('reason');
        $evidence = $request->input('employee_evidence');
        $statusId = 9; // Trạng thái yêu cầu trả hàng

        // Kiểm tra nếu có yêu cầu hoàn tiền, thì điền thông tin ngân hàng
        $bankAccount = $request->input('bank_account_number', null);
        $bankName = $request->input('bank_name', null);
        $bankQr = $request->input('bank_qr', null);

        $createdReturns = [];
        $totalRefundAmount = 0;

        // Lấy tổng tiền sản phẩm trong đơn hàng
        $order = Order::findOrFail($orderId);
        $totalProductAmount = $order->total_product_amount; // Total sản phẩm từ bảng orders

        // Lặp qua từng sản phẩm trong yêu cầu trả hàng
        foreach ($request->products as $product) {
            // Kiểm tra xem sản phẩm đã được trả hàng chưa
            $existingReturn = DB::table('order_returns')
                ->where('order_id', $orderId)
                ->where('product_id', $product['product_id'])
                ->where(function ($query) use ($product) {
                    if (!empty($product['product_variant_id'])) {
                        $query->where('product_variant_id', $product['product_variant_id']);
                    }
                })
                ->exists();

            if ($existingReturn) {
                return response()->json([
                    'message' => 'Sản phẩm đã được trả hàng.'
                ], 400);
            }

            // Lấy thông tin sản phẩm trong order_items
            $orderItem = DB::table('order_items')
                ->where('order_id', $orderId)
                ->where('product_id', $product['product_id'])
                ->when(!empty($product['product_variant_id']), function ($query) use ($product) {
                    $query->where('product_variant_id', $product['product_variant_id']);
                })
                ->first();

            if (!$orderItem) {
                return response()->json([
                    'message' => 'Sản phẩm ID ' . $product['product_id'] . ' không tồn tại trong đơn hàng.'
                ], 404);
            }

            // Kiểm tra số lượng trả lại có hợp lệ không
            if ($product['quantity'] > $orderItem->quantity) {
                return response()->json([
                    'message' => 'Số lượng trả vượt quá sản phẩm trong đơn hàng (ID ' . $product['product_id'] . ')'
                ], 400);
            }

            // Tính tỷ lệ hoàn trả cho sản phẩm này (tính theo tỷ lệ của tổng tiền sản phẩm)
            $productTotal = $orderItem->sell_price * $product['quantity'];
            $productRatio = $productTotal / $totalProductAmount;

            // Tính số tiền hoàn trả của sản phẩm này, áp dụng coupon (tính từ tỷ lệ của coupon)
            $couponDiscount = $order->coupon_discount_value ?? 0;
            $refundAmount = $productTotal - (($couponDiscount / 100) * $productTotal);
            $totalRefundAmount += $refundAmount;

            // Lưu thông tin trả hàng vào bảng order_returns
            $returnId = DB::table('order_returns')->insertGetId([
                'order_id' => $orderId,
                'product_id' => $product['product_id'],
                'product_variant_id' => $product['product_variant_id'] ?? null,
                'quantity_returned' => $product['quantity'],
                'reason' => $reason,
                'employee_evidence' => $evidence,
                'status_id' => $statusId,
                'price' => $refundAmount, // Lưu giá hoàn trả của sản phẩm vào cột price
                'bank_account_number' => $bankAccount, // Có thể null nếu không hoàn tiền
                'bank_name' => $bankName, // Có thể null nếu không hoàn tiền
                'bank_qr' => $bankQr, // Có thể null nếu không hoàn tiền
                'created_at' => now(),
                'updated_at' => now(),
                'total_refund_amount' => $refundAmount, // Lưu tổng tiền hoàn trả cho sản phẩm
            ]);

            $createdReturns[] = OrderReturn::with(['order', 'product', 'productVariant'])->find($returnId);
        }

        // Cập nhật trạng thái đơn hàng
        OrderOrderStatus::create([
            'order_id' => $orderId,
            'order_status_id' => $statusId,
            'modified_by' => $userId,
            'note' => 'Yêu cầu trả hàng' . ($bankAccount ? ' + hoàn tiền' : ''),
            'created_at' => now(),
            'updated_at' => now()
        ]);

        // Cập nhật trạng thái của đơn hàng
        Order::where('id', $orderId)->update(['status_id' => $statusId]);

        // Cập nhật tổng tiền hoàn trả vào bảng order_returns
        OrderReturn::where('order_id', $orderId)->update(['total_refund_amount' => $totalRefundAmount]);

        return response()->json([
            'message' => 'Trả hàng' . ($bankAccount ? ' và yêu cầu hoàn tiền' : '') . ' thành công!',
            'order_returns' => $createdReturns
        ], 200);
    }





    public function showByUser($userId)
    {
        // Lấy danh sách đơn hoàn trả với thông tin liên quan đến order, product, và productVariant
        $orderReturns = OrderReturn::with(['order', 'product', 'productVariant.attributeValues'])
            ->join('orders', 'order_returns.order_id', '=', 'orders.id') // Join bảng orders để lấy user_id
            ->where('orders.user_id', $userId) // Lọc theo user_id
            ->get()
            ->groupBy('order_id') // Nhóm theo order_id
            ->map(function ($returns, $orderId) {
                $firstReturn = $returns->first(); // Lấy bản hoàn trả đầu tiên trong nhóm
                $order = $firstReturn->order; // Lấy thông tin đơn hàng

                return [
                    'order_id' => $orderId,
                    'reason' => $firstReturn->reason, // Lý do hoàn trả
                    'employee_evidence' => $firstReturn->employee_evidence, // Video chứng minh
                    'order' => $order ? $order->toArray() : null, // Chuyển thông tin đơn hàng thành mảng
                    'order_returns' => $returns->map(function ($return) {
                        $product = $return->product;
                        $variant = $return->productVariant;

                        // Trả về thông tin chi tiết của từng đơn hoàn trả
                        return [
                            'order_return_id' => $return->id,
                            'product' => [
                                'product_id' => $product->id,
                                'name' => $product->name,
                                'thumbnail' => $variant ? $variant->thumbnail : $product->thumbnail,
                                'sell_price' => $variant ? $variant->sell_price : $return->price,
                                'product_variant_id' => $return->product_variant_id,
                                'quantity' => $return->quantity_returned,
                                'attributes' => $variant ? $variant->attributeValues->map(function ($attr) {
                                    return [
                                        'attribute_name' => $attr->value,
                                        'attribute_id' => $attr->attribute_id,
                                    ];
                                }) : [], // Giữ lại danh sách thuộc tính nếu có
                            ]
                        ];
                    })->values(),
                ];
            })->values(); // Trả về tất cả các đơn hoàn trả đã nhóm theo order_id

        if ($orderReturns->isEmpty()) {
            return response()->json([
                'message' => 'Không tìm thấy đơn hàng trả lại cho người dùng này.',
            ], 404);
        }

        return response()->json([
            'order_returns' => $orderReturns, // Trả về tất cả dữ liệu đã xử lý
        ], 200);
    }



    // Admin xử lý chấp nhận hoặc từ chối yêu cầu trả hàng
    public function updateStatusByOrder(Request $request, $orderId)
    {
        $request->validate([
            'status_id' => 'required|in:10,11', // 10: Chấp nhận trả hàng, 11: Từ chối
            'note' => 'nullable|string',
            'user_id' => 'required|exists:users,id',
        ]);

        $orderReturns = OrderReturn::where('order_id', $orderId)->get();
        $order = Order::findOrFail($orderId);

        DB::beginTransaction();
        try {
            foreach ($orderReturns as $orderReturn) {
                // Cập nhật từng bản ghi trả hàng
                $orderReturn->update(['status_id' => $request->status_id]);
            }

            // Cập nhật trạng thái đơn hàng
            $order->update(['status_id' => $request->status_id]);

            // Ghi log trạng thái
            OrderOrderStatus::create([
                'order_id' => $orderId,
                'order_status_id' => $request->status_id,
                'modified_by' => $request->user_id,
                'note' => $request->note ?? ($request->status_id == 10 ? 'Admin chấp nhận trả hàng' : 'Admin từ chối trả hàng'),
                'created_at' => now(),
                'updated_at' => now()
            ]);

            DB::commit();
            return response()->json(['message' => 'Cập nhật trạng thái trả hàng theo đơn thành công!']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Lỗi xử lý trả hàng theo đơn', 'error' => $e->getMessage()], 500);
        }
    }
    public function confirmRefundByOrder(Request $request, $orderId)
    {
        $request->validate([
            'refund_proof' => 'required|string',
            'note' => 'nullable|string',
            'user_id' => 'required|exists:users,id'
        ]);

        $returns = OrderReturn::where('order_id', $orderId)->get();

        if ($returns->isEmpty()) {
            return response()->json(['message' => 'Không tìm thấy yêu cầu trả hàng'], 404);
        }

        // Kiểm tra trạng thái đơn hàng, chỉ cho phép hoàn tiền khi trạng thái là 10 (Chấp nhận trả hàng)
        $order = Order::findOrFail($orderId);

        if ($order->status_id != 10) {
            return response()->json(['message' => 'Chỉ có thể hoàn tiền khi yêu cầu trả hàng đã được chấp nhận.'], 400);
        }

        DB::beginTransaction();
        try {
            foreach ($returns as $return) {
                $return->update([
                    'status_id' => 12,
                    'refund_proof' => $request->refund_proof
                ]);
            }

            Order::where('id', $orderId)->update(['status_id' => 12]);

            OrderOrderStatus::create([
                'order_id' => $orderId,
                'order_status_id' => 12,
                'modified_by' => $request->user_id,
                'note' => $request->note ?? 'Hoàn tiền thành công',
                'created_at' => now(),
                'updated_at' => now()
            ]);

            DB::commit();
            return response()->json(['message' => 'Xác nhận hoàn tiền thành công']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Lỗi xác nhận hoàn tiền',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
