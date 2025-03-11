<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\CartItem;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class CartItemController extends Controller
{

    public function index(Request $request)
    {
        $user = Auth::guard('sanctum')->user();
        $userId = $user ? $user->id : null;

        Log::info('Lấy giỏ hàng:', ['Auth ID' => $userId]);

        if ($userId) {
            $cartItems = CartItem::where('user_id', $userId)
                ->with(['product', 'productVariant'])
                ->get();

            return response()->json([
                'cart_items' => $cartItems,
                'user_id' => $userId
            ]);
        }

        return response()->json([
            'cart_items' => [],
            'message' => 'Không tìm thấy giỏ hàng'
        ], 404);
    }

    public function store(Request $request, $productId)
    {

        try {
            $user = Auth::guard('sanctum')->user();
            $userId = $user ? $user->id : null;
            $productVariantId = $request->input('product_variant_id', null);
            $quantity = $request->input('quantity', 1);
            if (!$userId) {
                return response()->json([
                    'message' => 'Khách vãng lai: Lưu giỏ hàng trên localStorage'
                ], 200);
            }            

            Log::info('Kiểm tra trước khi thêm vào giỏ hàng:', [
                'Auth ID' => $userId,
                'Product ID' => $productId,
                'Product Variant ID' => $productVariantId
            ]);

            /// Kiểm tra tồn kho
            if ($productVariantId) {
                $productVariant = ProductVariant::where('product_id', $productId)->findOrFail($productVariantId);
                $availableStock = $productVariant->stock;
                $price = $productVariant->sale_price ?? $productVariant->sell_price;
            } else {
                $product = Product::findOrFail($productId);
                $availableStock = $product->stock;
                $price = $product->sale_price ?? $product->sell_price;
            }

            if ($availableStock < $quantity) {
                return response()->json(['message' => 'Sản phẩm không đủ số lượng tồn kho'], 400);
            }

           
            // Kiểm tra tổng số lượng đã có trong giỏ hàng
            $existingCartItem = CartItem::where('product_id', $productId)
                ->where('product_variant_id', $productVariantId)
                ->where('user_id', $userId)
                ->first();
            $cartQuantity = $existingCartItem ? $existingCartItem->quantity : 0;

            // Kiểm tra nếu tổng số lượng vượt quá tồn kho
            if (($cartQuantity + $quantity) > $availableStock) {
                return response()->json([
                    'message' => 'Không đủ tồn kho. Chỉ còn ' . $availableStock . ' sản phẩm.'
                ], 400);
            }

            if ($existingCartItem) {
                $existingCartItem->increment('quantity', $quantity);
            } else {
                $cartItem = CartItem::create([
                    'user_id' => $userId,
                    'product_id' => $productId,
                    'product_variant_id' => $productVariantId,
                    'quantity' => $quantity,
                    'price' => $price
                ]);
            }
            //Check validate stock khi thêm số lượng sản phẩm

            return response()->json([
                'message' => 'Sản phẩm đã thêm vào giỏ hàng',
                'cart_item' => $cartItem ?? $existingCartItem
            ]);
        } catch (\Exception $e) {
            Log::error(' Lỗi khi thêm sản phẩm vào giỏ hàng:', [
                'error' => $e->getMessage()
            ]);
            return response()->json(['message' => 'Lỗi khi thêm sản phẩm vào giỏ hàng'], 500);
        }
    }


    public function update(Request $request, $productId, $variantId = null)
    {
        $request->validate(['quantity' => 'required|integer|min:1']);

        $user = Auth::guard('sanctum')->user();
        $userId = $user ? $user->id : null;

        $cartItem = CartItem::where('product_id', $productId)
            ->where('product_variant_id', $variantId)
            ->where('user_id', $userId)
            ->first();

        if (!$cartItem) {
            return response()->json(['message' => 'Không tìm thấy sản phẩm trong giỏ hàng'], 404);
        }

        $stock = $variantId ? ProductVariant::where('id', $variantId)->value('stock') : Product::where('id', $productId)->value('stock');

        if ($stock < $request->quantity) {
            return response()->json(['message' => 'Số lượng sản phẩm trong kho không đủ'], 400);
        }

        $cartItem->update(['quantity' => $request->quantity]);
        return response()->json(['message' => 'Cập nhật số lượng thành công']);
    }


    public function destroy(Request $request, $productId, $variantId = null)
    {
        $user = Auth::guard('sanctum')->user();
        $userId = $user ? $user->id : null;

        $deleted = CartItem::where('product_id', $productId)
        ->where('product_variant_id', $variantId)
        ->where('user_id', $userId)
        ->delete();

        if ($deleted) {
            return response()->json(['message' => 'Sản phẩm đã được xóa']);
        }

        return response()->json(['message' => 'Không tìm thấy sản phẩm trong giỏ hàng'], 404);
    }

}
