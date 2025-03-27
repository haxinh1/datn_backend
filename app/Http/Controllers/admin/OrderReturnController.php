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
        $orderReturns = OrderReturn::with(['product', 'productVariant.attributeValues'])->get();

        $detailedReturns = $orderReturns->map(function ($return) {
            $product = $return->product;
            $variant = $return->productVariant;

            return [
                'id' => $return->id,
                'order_id' => $return->order_id,
                'product_id' => $product->id,
                'name' => $product->name,
                'thumbnail' => $variant?->thumbnail ?? $product->thumbnail,
                'sell_price' => $variant?->sell_price ?? $return->price,
                'quantity_returned' => $return->quantity_returned,
                'reason' => $return->reason,
                'employee_evidence' => $return->employee_evidence,
                'status_id' => $return->status_id,
                'attributes' => $variant ? $variant->attributeValues->map(function ($attr) {
                    return [
                        'attribute_name' => $attr->value,
                        'attribute_id' => $attr->attribute_id,
                    ];
                }) : [],
                'created_at' => $return->created_at,
                'updated_at' => $return->updated_at,
            ];
        });

        return response()->json([
            'order_returns' => $detailedReturns,
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
            'user_id' => 'required|integer|exists:users,id',
            'reason' => 'required|string|max:255',
            'employee_evidence' => 'nullable|string',
            'products' => 'required|array|min:1',
            'products.*.product_id' => 'required|integer|exists:products,id',
            'products.*.product_variant_id' => 'nullable|integer|exists:product_variants,id',
            'products.*.quantity' => 'required|integer|min:1',
        ]);

        $userId = $request->input('user_id');
        $reason = $request->input('reason');
        $evidence = $request->input('employee_evidence');

        $createdReturns = [];

        foreach ($request->products as $product) {

            // Kiểm tra đã tồn tại trả hàng chưa
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

            // Lấy order item
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

            // Kiểm tra số lượng hợp lệ
            if ($product['quantity'] > $orderItem->quantity) {
                return response()->json([
                    'message' => 'Số lượng trả vượt quá sản phẩm trong đơn hàng (ID ' . $product['product_id'] . ')'
                ], 400);
            }

            // Lưu trả hàng
            $returnId = DB::table('order_returns')->insertGetId([
                'order_id' => $orderId,
                'product_id' => $product['product_id'],
                'product_variant_id' => $product['product_variant_id'] ?? null,
                'quantity_returned' => $product['quantity'],
                'reason' => $reason,
                'employee_evidence' => $evidence,
                'status_id' => 9,
                'price' => $orderItem->sell_price,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $createdReturns[] = OrderReturn::with(['order', 'product', 'productVariant'])->find($returnId);
        }

        DB::table('order_order_statuses')->insert([
            'order_id' => $orderId,
            'order_status_id' => 9,
            'modified_by' => $userId,
            'note' => 'Khách hàng yêu cầu trả hàng',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Cập nhật trạng thái đơn hàng
        DB::table('orders')->where('id', $orderId)->update(['status_id' => 9]);

        // Lấy lại toàn bộ order_items (giống như bạn làm)
        $orderItems = OrderItem::where('order_id', $orderId)
            ->with(['product', 'productVariant.attributeValues'])
            ->get();

        // Group lại để trả về dạng giống OrderItemController
        $groupedItems = $orderItems->groupBy('product_variant_id')->map(function ($variantItems) {
            if ($variantItems->first()->product_variant_id !== null) {
                $product = $variantItems->first()->product;

                return [
                    'product_id' => $product->id,
                    'name' => $product->name,
                    'thumbnail' => $product->thumbnail,
                    'sell_price' => $variantItems->first()->sell_price,
                    'quantity' => $variantItems->sum('quantity'),
                    'variants' => $variantItems->map(function ($item) {
                        return [
                            'variant_id' => $item->productVariant->id,
                            'sell_price' => $item->productVariant->sell_price,
                            'quantity' => $item->quantity,
                            'variant_thumbnail' => $item->productVariant->thumbnail,
                            'attributes' => $item->productVariant->attributeValues->map(function ($attr) {
                                return [
                                    'attribute_name' => $attr->value,
                                    'attribute_id' => $attr->attribute_id
                                ];
                            }),
                        ];
                    }),
                ];
            }
        })->filter();

        $simpleProducts = $orderItems->whereNull('product_variant_id')->map(function ($item) {
            return [
                'product_id' => $item->product_id,
                'name' => $item->product->name,
                'thumbnail' => $item->product->thumbnail,
                'sell_price' => $item->sell_price,
                'quantity' => $item->quantity,
                'variants' => []
            ];
        });

        $finalItems = $groupedItems->values()->merge($simpleProducts);

        return response()->json([
            'message' => 'Trả hàng thành công!',
            'order_returns' => $createdReturns,
            'order_items' => $finalItems
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
