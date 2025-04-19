<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderReturn;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderItemController extends Controller
{
    /**
     * Lấy danh sách sản phẩm trong đơn hàng
     */
    public function index($orderId)
    {
        // Lấy tất cả các order items của đơn hàng
        $items = OrderItem::where('order_id', $orderId)
            ->with(['product', 'productVariant.attributeValues'])
            ->orderBy('created_at', 'desc')
            ->get();

        // Nhóm theo product_variant_id (biến thể)
        $groupedItems = $items->groupBy('product_variant_id')->map(function ($variantItems) {
            // Kiểm tra nếu có biến thể
            if ($variantItems->first()->product_variant_id !== null) {
                $product = $variantItems->first()->product;

                // Tính tổng quantity cho mỗi biến thể
                return [
                    'product_id' => $product->id,
                    'name' => $product->name,
                    'thumbnail' => $product->thumbnail,
                    'sell_price' => $variantItems->first()->sell_price,
                    'quantity' => $variantItems->sum('quantity'),
                    'refund_amount' => $variantItems->first()->refund_amount,
                    'has_reviewed' => $variantItems->first()->has_reviewed == 1 ? true : false,
                    'variants' => $variantItems->map(function ($item) {
                        return [
                            'variant_id' => $item->productVariant->id,
                            'sell_price' => $item->productVariant->sell_price,
                            'quantity' => $item->quantity,
                            'variant_thumbnail' => $item->productVariant->thumbnail,
                            'attributes' => $item->productVariant->attributeValues->map(function ($attributeValue) {
                                return [
                                    'attribute_name' => $attributeValue->value,
                                    'attribute_id' => $attributeValue->attribute_id
                                ];
                            }),
                        ];
                    }),
                ];
            }
        })->filter();

        // Xử lý sản phẩm đơn (không có biến thể)
        $simpleProducts = $items->filter(function ($item) {
            return $item->product_variant_id === null;
        })->map(function ($item) {
            return [
                'product_id' => $item->product_id,
                'name' => $item->product->name,
                'thumbnail' => $item->product->thumbnail,
                'sell_price' => $item->sell_price,
                'quantity' => $item->quantity,
                'refund_amount' => $item->refund_amount,
                'has_reviewed' => $item->has_reviewed == 1 ? true : false,
                'variants' => []
            ];
        });

        // Gộp lại sản phẩm đơn và sản phẩm có biến thể
        $finalItems = $groupedItems->values()->merge($simpleProducts);

        return response()->json($finalItems, 200);
    }

    public function getTopProductsByUser($userId)
    {
        $validStatuses = [7, 11, 14];

        $orderItems = OrderItem::select('product_id', 'product_variant_id', DB::raw('SUM(quantity) as total_quantity'))
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->where('orders.user_id', $userId)
            ->whereIn('orders.status_id', $validStatuses)
            ->groupBy('product_id', 'product_variant_id')
            ->orderByDesc('total_quantity')
            ->get();

        $topProducts = $orderItems->map(function ($item) {
            $product = Product::find($item->product_id);

            if ($item->product_variant_id) {
                $productVariant = ProductVariant::find($item->product_variant_id);
                $attributes = $productVariant ? $productVariant->attributeValues->map(function ($attributeValue) {
                    return [
                        'attribute_name' => $attributeValue->value,
                        'attribute_id' => $attributeValue->attribute_id
                    ];
                }) : [];

                return [
                    'product_id' => $product->id,
                    'name' => $product->name,
                    'thumbnail' => $product->thumbnail,
                    'sell_price' => $productVariant ? $productVariant->sell_price : 0,
                    'quantity' => $item->total_quantity,
                    'variant' => [
                        'variant_id' => $productVariant ? $productVariant->id : null,
                        'variant_thumbnail' => $productVariant ? $productVariant->thumbnail : null,
                        'attributes' => $attributes,
                    ]
                ];
            } else {
                return [
                    'product_id' => $product->id,
                    'name' => $product->name,
                    'thumbnail' => $product->thumbnail,
                    'sell_price' => $product->sell_price,
                    'quantity' => $item->total_quantity,
                    'variant' => []
                ];
            }
        });

        return response()->json([
            'status' => 'success',
            'top_products' => $topProducts
        ], 200);
    }
}
