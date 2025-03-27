<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
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
            ->with(['product', 'productVariant.attributeValues']) // Nạp đúng quan hệ
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
                    'quantity' => $variantItems->sum('quantity'), // Quantity tính từ biến thể
                    'variants' => $variantItems->map(function ($item) {
                        return [
                            'variant_id' => $item->productVariant->id,
                            'sell_price' => $item->productVariant->sell_price,
                            'quantity' => $item->quantity,
                            'variant_thumbnail' => $item->productVariant->thumbnail,
                            'attributes' => $item->productVariant->attributeValues->map(function ($attributeValue) {
                                return [
                                    'attribute_name' => $attributeValue->value, // Lấy tên thuộc tính (value)
                                    'attribute_id' => $attributeValue->attribute_id // ID thuộc tính
                                ];
                            }),
                        ];
                    }),
                ];
            }
        })->filter();  // Loại bỏ các nhóm mà không có `product_variant_id`

        // Xử lý sản phẩm đơn (không có biến thể)
        $simpleProducts = $items->filter(function ($item) {
            return $item->product_variant_id === null;  // Sản phẩm không có biến thể
        })->map(function ($item) {
            return [
                'product_id' => $item->product_id,
                'name' => $item->product->name,
                'thumbnail' => $item->product->thumbnail,
                'sell_price' => $item->sell_price,
                'quantity' => $item->quantity, // Lấy quantity của sản phẩm đơn
                'variants' => []  // Không có biến thể
            ];
        });

        // Gộp lại sản phẩm đơn và sản phẩm có biến thể
        $finalItems = $groupedItems->values()->merge($simpleProducts);

        return response()->json($finalItems, 200);
    }
    public function getTopProductsByUser($userId)
    {
        // Truy vấn tất cả order_items liên kết với user_id
        $orderItems = OrderItem::select('product_id', 'product_variant_id', DB::raw('SUM(quantity) as total_quantity'))
            ->join('orders', 'order_items.order_id', '=', 'orders.id') // Liên kết với bảng orders
            ->where('orders.user_id', $userId) // Lọc theo user_id
            ->groupBy('product_id', 'product_variant_id') // Nhóm theo product_id và product_variant_id
            ->orderByDesc('total_quantity') // Sắp xếp theo số lượng giảm dần
            ->get();

        // Lấy thông tin sản phẩm cho mỗi sản phẩm đã mua
        $topProducts = $orderItems->map(function ($item) {
            $product = Product::find($item->product_id);

            // Lấy thông tin về biến thể nếu có
            if ($item->product_variant_id) {
                $productVariant = $product->variants()->find($item->product_variant_id);
                $attributes = $productVariant ? $productVariant->attributeValues->map(function ($attributeValue) {
                    return [
                        'attribute_name' => $attributeValue->value,  // Tên thuộc tính
                        'attribute_id' => $attributeValue->attribute_id  // ID thuộc tính
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
                // Nếu là sản phẩm không có biến thể
                return [
                    'product_id' => $product->id,
                    'name' => $product->name,
                    'thumbnail' => $product->thumbnail,
                    'sell_price' => $product->sell_price,
                    'quantity' => $item->total_quantity,
                    'variant' => []  // Sản phẩm đơn không có biến thể
                ];
            }
        });

        return response()->json([
            'status' => 'success',
            'top_products' => $topProducts
        ], 200);
    }
}
