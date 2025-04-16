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

        Log::info('Lấy giỏ hàng:', ['userId' => $userId]);

        if ($userId) {
            $cartItems = CartItem::where('user_id', $userId)
                ->with(['product', 'productVariant'])
                ->get();
            $now = now();
            foreach ($cartItems as $item) {
                if ($item->productVariant) {
                    $variant = $item->productVariant;
                    $item->price = (
                        $variant->sale_price &&
                        $variant->sale_price_start_at &&
                        $variant->sale_price_end_at &&
                        $now->between($variant->sale_price_start_at, $variant->sale_price_end_at)
                    ) ? $variant->sale_price : $variant->sell_price;
                } else {
                    $product = $item->product;
                    $item->price = (
                        $product->sale_price &&
                        $product->sale_price_start_at &&
                        $product->sale_price_end_at &&
                        $now->between($product->sale_price_start_at, $product->sale_price_end_at)
                    ) ? $product->sale_price : $product->sell_price;
                }
            }

            return response()->json([
                'cart_items' => $cartItems,
                'user_id' => $userId
            ]);
        } else {
            $cartItems = $request->input('cart_items', []);  

            if (empty($cartItems)) {
                return response()->json([
                    'cart_items' => [],
                    'message' => 'Giỏ hàng trống'
                ], 200);
            }

            Log::info('Giỏ hàng lấy từ frontend:', ['cart' => $cartItems]);

            return response()->json([
                'cart_items' => $cartItems, 
                'message' => 'Giỏ hàng lấy từ frontend'
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

            Log::info('Thêm vào giỏ hàng:', [
                'userId' => $userId,
                'Product ID' => $productId,
                'Product Variant ID' => $productVariantId
            ]);

            if ($productVariantId) {
                $productVariant = ProductVariant::where('product_id', $productId)->findOrFail($productVariantId);
                $availableStock = $productVariant->stock;
            } else {
                $product = Product::findOrFail($productId);
                $availableStock = $product->stock;
            }

            if ($availableStock < $quantity) {
                return response()->json(['message' => 'Sản phẩm không đủ số lượng tồn kho'], 400);
            }

            if ($userId) {
                $existingCartItem = CartItem::where('product_id', $productId)
                    ->where('product_variant_id', $productVariantId)
                    ->where('user_id', $userId)
                    ->first();

                $cartQuantity = $existingCartItem ? $existingCartItem->quantity : 0;

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
                    ]);
                }

                return response()->json([
                    'message' => 'Sản phẩm đã thêm vào giỏ hàng (Database)',
                    'cart_item' => $cartItem ?? $existingCartItem
                ]);
            } else {
                $cartItems = $request->input('cart_items', []);  

                $key = $productId . '-' . ($productVariantId ?? 'default');  

                $cartQuantity = isset($cartItems[$key]) ? $cartItems[$key]['quantity'] : 0;

                if (($cartQuantity + $quantity) > $availableStock) {
                    return response()->json(['message' => 'Không đủ tồn kho. Chỉ còn ' . $availableStock . ' sản phẩm.'], 400);
                }

                if (isset($cartItems[$key])) {
                    $cartItems[$key]['quantity'] += $quantity;
                } else {
                    $cartItems[$key] = [
                        'product_id' => $productId,
                        'product_variant_id' => $productVariantId,
                        'quantity' => $quantity,
                    ];
                }

                return response()->json([
                    'message' => 'Sản phẩm đã thêm vào giỏ hàng (Frontend)',
                    'cart_items' => $cartItems  
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Lỗi khi thêm sản phẩm vào giỏ hàng:', [
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

        if ($userId) {
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

            if ($request->input('add_quantity', false)) {
                $cartItem->increment('quantity', $request->quantity);  
            } else {
                $cartItem->update(['quantity' => $request->quantity]);
            }
            return response()->json(['message' => 'Cập nhật số lượng thành công']);
        } else {
            $cartItems = $request->input('cart_items', []);  

            $key = $productId . '-' . ($variantId ?? 'default');  

            if (!isset($cartItems[$key])) {
                return response()->json(['message' => 'Không tìm thấy sản phẩm trong giỏ hàng'], 404);
            }
            $stock = $variantId ? ProductVariant::where('id', $variantId)->value('stock') : Product::where('id', $productId)->value('stock');

            if ($stock < $request->quantity) {
                return response()->json(['message' => 'Số lượng sản phẩm trong kho không đủ'], 400);
            }

            $oldQuantity = $cartItems[$key]['quantity'];

            $cartItems[$key]['quantity'] = $oldQuantity + $request->quantity;


            return response()->json(['message' => 'Cập nhật số lượng thành công (Frontend)', 'cart_items' => $cartItems]);
        }
    }



    public function destroy(Request $request, $productId, $variantId = null)
    {
        $user = Auth::guard('sanctum')->user();
        $userId = $user ? $user->id : null;

        if ($userId) {
            $deleted = CartItem::where('product_id', $productId)
                ->where('product_variant_id', $variantId)
                ->where('user_id', $userId)
                ->delete();

            if ($deleted) {
                return response()->json(['message' => 'Sản phẩm đã được xóa']);
            } else {
                return response()->json(['message' => 'Không tìm thấy sản phẩm trong giỏ hàng'], 404);
            }
        } else {
            $cartItems = $request->input('cart_items', []);  

            $key = $productId . '-' . ($variantId ?? 'default');

            if (!isset($cartItems[$key])) {
                return response()->json(['message' => 'Không tìm thấy sản phẩm trong giỏ hàng'], 404);
            }

            unset($cartItems[$key]);

            return response()->json(['message' => 'Sản phẩm đã được xóa (Frontend)', 'cart_items' => $cartItems]);
        }
    }
    public function destroyAll(Request $request)
    {
        $user = Auth::guard('sanctum')->user();
        $userId = $user ? $user->id : null;

        if ($userId) {
            CartItem::where('user_id', $userId)->delete();

            return response()->json(['message' => 'Giỏ hàng đã được xóa (Database)']);
        } else {
            $cartItems = $request->input('cart_items', []);  

            $cartItems = [];

            return response()->json(['message' => 'Giỏ hàng đã được xóa (Frontend)', 'cart_items' => $cartItems]);
        }
    }
}
