<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderReturn;
use App\Models\OrderOrderStatus;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OrderReturnController extends Controller
{
    /**
     * Lấy danh sách các đơn hàng trả lại
     */
    public function index(Request $request)
    {
        $orderId = $request->query('order_id');

        // Query để lấy thông tin trả hàng với các chi tiết liên quan
        $query = OrderReturn::with(['order', 'product', 'productVariant.attributeValues']); // Quan hệ để lấy productVariant và attributeValues

        if ($orderId) {
            $query->where('order_id', $orderId);
        }

        // Lấy danh sách đơn trả hàng theo order_id, sắp xếp theo thời gian tạo
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
                    'total_refund_amount' => $returns->first()->total_refund_amount,
                    'refund_proof' => $returns->first()->refund_proof,
                    'bank_account_number' => $firstReturn->bank_account_number,
                    'bank_name' => $firstReturn->bank_name,
                    'bank_qr' => $firstReturn->bank_qr,
                    'order' => $order ? $order->toArray() : null,
                    'products' => $returns->map(function ($return) {
                        $product = $return->product;
                        $variant = $return->productVariant;
                        $attributes = [];

                        // Lấy thông tin thuộc tính nếu có productVariant
                        if ($variant) {
                            $attributes = $variant->attributeValues->map(function ($attr) {
                                return [
                                    'attribute_name' => $attr->value,
                                    'attribute_id' => $attr->attribute_id,
                                ];
                            })->toArray();
                        }

                        // Lấy thông tin về từng sản phẩm trả hàng
                        return [
                            'product_id' => $product->id,
                            'name' => $product->name,
                            'thumbnail' => $variant ? $variant->thumbnail : $product->thumbnail,
                            'price' => $return->price,
                            'sell_price' => $return->sell_price,
                            'product_variant_id' => $return->product_variant_id,
                            'quantity' => $return->quantity_returned,
                            'attributes' => $attributes,  // Trả về danh sách thuộc tính nếu có
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
                $order = $firstReturn->order;

                return [
                    'order_id' => $orderId,
                    'reason' => $firstReturn->reason,
                    'employee_evidence' => $firstReturn->employee_evidence,
                    'total_refund_amount' => $returns->first()->total_refund_amount,
                    'refund_proof' => $returns->first()->refund_proof,
                    'bank_account_number' => $firstReturn->bank_account_number,
                    'bank_name' => $firstReturn->bank_name,
                    'bank_qr' => $firstReturn->bank_qr,
                    'order' => $order ? $order->toArray() : null,
                    'products' => $returns->map(function ($return) {
                        $product = $return->product;
                        $variant = $return->productVariant;

                        // Trả về thông tin chi tiết của từng sản phẩm trả hàng
                        return [
                            'product_id' => $product->id,
                            'name' => $product->name,
                            'thumbnail' => $variant ? $variant->thumbnail : $product->thumbnail,
                            'price' => $return->price, // Giá đã tính toán cho sản phẩm
                            'sell_price' => $return->sell_price,
                            'product_variant_id' => $return->product_variant_id,
                            'quantity' => $return->quantity_returned,
                            'attributes' => $variant ? $variant->attributeValues->map(function ($attr) {
                                return [
                                    'attribute_name' => $attr->value,
                                    'attribute_id' => $attr->attribute_id,
                                ];
                            }) : [], // Lấy các thuộc tính của biến thể nếu có
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

    public function showByUser($userId)
    {
        // Lấy danh sách đơn hoàn trả với thông tin liên quan đến order, product, và productVariant
        $orderReturns = OrderReturn::with(['order', 'product', 'productVariant.attributeValues'])
            ->join('orders', 'order_returns.order_id', '=', 'orders.id')
            ->where('orders.user_id', $userId)
            ->orderBy('orders.created_at', 'desc')
            ->get()
            ->groupBy('order_id')
            ->map(function ($returns, $orderId) {
                $firstReturn = $returns->first();
                $order = $firstReturn->order;

                return [
                    'order_id' => $orderId,
                    'reason' => $returns->first()->reason,
                    'employee_evidence' => $returns->first()->employee_evidence,
                    'total_refund_amount' => $returns->first()->total_refund_amount,
                    'refund_proof' => $returns->first()->refund_proof,
                    'bank_account_number' => $firstReturn->bank_account_number,
                    'bank_name' => $firstReturn->bank_name,
                    'bank_qr' => $firstReturn->bank_qr,
                    'order' => $order ? $order->toArray() : null,
                    'products' => $returns->map(function ($return) {
                        $product = $return->product;
                        $variant = $return->productVariant;
                        $attributes = [];

                        // Lấy thông tin thuộc tính nếu có productVariant
                        if ($variant) {
                            $attributes = $variant->attributeValues->map(function ($attr) {
                                return [
                                    'attribute_name' => $attr->value,
                                    'attribute_id' => $attr->attribute_id,
                                ];
                            })->toArray();
                        }

                        // Lấy thông tin về từng sản phẩm trả hàng
                        return [
                            'product_id' => $product->id,
                            'name' => $product->name,
                            'thumbnail' => $variant ? $variant->thumbnail : $product->thumbnail,
                            'price' => $return->price,
                            'sell_price' => $return->sell_price,
                            'product_variant_id' => $return->product_variant_id,
                            'quantity' => $return->quantity_returned,
                            'attributes' => $attributes,
                        ];
                    })->values(),
                ];
            })->values();

        return response()->json([
            'order_returns' => $orderReturns,
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
            if ($order->coupon_discount_type === 'percent') {
                $refundAmount = $productTotal - (($order->coupon_discount_value / 100) * $productTotal);
            } elseif ($order->coupon_discount_type === 'fix_amount' && $totalProductAmount > 0) {
                $refundAmount = $productTotal - ($productRatio * $order->coupon_discount_value);
            } else {
                $refundAmount = $productTotal;
            }

            $pointsUsed = $order->used_points ?? 0;
            log::info('Số điểm đã sử dụng: ' . $pointsUsed);
            $pointsValue = 1;
            $pointsRefundAmount = ($pointsUsed * $productRatio) * $pointsValue;

            // Trừ tiền hoàn trả theo điểm tiêu dùng
            $refundAmount -= $pointsRefundAmount;

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
                'sell_price' => $orderItem->sell_price, // Lưu giá bán của sản phẩm vào cột sell_price
                'price' => $refundAmount, // Lưu giá hoàn trả của sản phẩm vào cột price
                'bank_account_number' => $bankAccount, // Có thể null nếu không hoàn tiền
                'bank_name' => $bankName, // Có thể null nếu không hoàn tiền
                'bank_qr' => $bankQr, // Có thể null nếu không hoàn tiền
                'created_at' => now(),
                'updated_at' => now(),
                'total_refund_amount' => $refundAmount, // Lưu tổng tiền hoàn trả cho sản phẩm
            ]);

            $createdReturns[] = OrderReturn::with(['order', 'product', 'productVariant'])->find($returnId);
            // **Cập nhật total_sales trong bảng products**:
            $productUpdate = Product::find($product['product_id']);
            if ($productUpdate) {
                $productUpdate->total_sales -= $product['quantity'];
                $productUpdate->save();  
            }
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
        // Order::where('id', $orderId)->update(['status_id' => $statusId]);
        try {
            $order = Order::find($orderId);
            $order->status_id = $statusId;
            $order->save();
        } catch (\Exception $e) {
            Log::error('Lỗi cập nhật đơn hàng: ' . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }

        // Cập nhật tổng tiền hoàn trả vào bảng order_returns
        OrderReturn::where('order_id', $orderId)->update(['total_refund_amount' => $totalRefundAmount]);

        return response()->json([
            'message' => 'Trả hàng' . ($bankAccount ? ' và yêu cầu hoàn tiền' : '') . ' thành công!',
            'order_returns' => $createdReturns
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
    public function approveReturn(Request $request, $orderId)
    {
        // Lấy tất cả các đơn hàng trả lại nhóm theo order_id
        $orderReturns = OrderReturn::where('order_id', $orderId)->get();

        if ($orderReturns->isEmpty()) {
            return response()->json(['message' => 'No order returns found for this order'], 404);
        }

        // Lấy tham số từ frontend (approve_stock)
        $approveStock = $request->input('approve_stock', false); // Mặc định là false nếu không có

        // Duyệt qua tất cả các đơn hoàn trả trong nhóm
        foreach ($orderReturns as $orderReturn) {
            if ($approveStock) {
                // Nếu chọn cộng stock
                if ($orderReturn->product_variant_id) {
                    $productVariant = \App\Models\ProductVariant::find($orderReturn->product_variant_id);
                    if ($productVariant) {
                        $productVariant->increment('stock', $orderReturn->quantity_returned);
                    }
                } else {
                    $product = \App\Models\Product::find($orderReturn->product_id);
                    if ($product) {
                        $product->increment('stock', $orderReturn->quantity_returned);
                    }
                }
            }

            // Chuyển trạng thái đơn hàng trả lại sang 14 (Người bán đã nhận hàng)
            $orderReturn->status_id = 14;  // Sử dụng status_id thay vì status
            $orderReturn->save();
        }

        // Cập nhật trạng thái đơn hàng chính (Order) sang 14 nếu tất cả đơn hoàn trả đã hoàn tất
        $order = Order::find($orderId);
        if ($order && $order->status_id == 13) {
            $order->status_id = 14; // Chuyển trạng thái đơn chính sang 14
            $order->save();
        }

        return response()->json(['message' => 'All returns processed, stock updated (if applicable), and order status updated to 14']);
    }
}
