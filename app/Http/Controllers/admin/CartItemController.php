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
    // Kiểm tra nếu người dùng đã đăng nhập
    $user = Auth::guard('sanctum')->user();
    $userId = $user ? $user->id : null;

    Log::info('Lấy giỏ hàng:', ['userId' => $userId]);

    if ($userId) {
        // Nếu đã đăng nhập, lấy giỏ hàng từ database
        $cartItems = CartItem::where('user_id', $userId)
            ->with(['product', 'productVariant'])
            ->get();

        return response()->json([
            'cart_items' => $cartItems,
            'user_id' => $userId
        ]);
    } else {
        // Nếu chưa đăng nhập, lấy giỏ hàng từ session (cho khách vãng lai)
        $sessionCart = session()->get('cart', []);
        return response()->json([
            'cart_items' => array_values($sessionCart),
            'message' => 'Giỏ hàng lấy từ session'
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
            $user = Auth::guard('sanctum')->user();  // Kiểm tra người dùng đã đăng nhập qua token
            $userId = $user ? $user->id : null;
            $productVariantId = $request->input('product_variant_id', null);
            $quantity = $request->input('quantity', 1);

            Log::info('Thêm vào giỏ hàng:', [
                'userId' => $userId,
                'Product ID' => $productId,
                'Product Variant ID' => $productVariantId
            ]);

            // Kiểm tra tồn kho
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

            if ($userId) {
                // Nếu đã đăng nhập → Lưu vào database
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

                return response()->json([
                    'message' => 'Sản phẩm đã thêm vào giỏ hàng (Database)',
                    'cart_item' => $cartItem ?? $existingCartItem
                ]);
            } else {
                // Nếu chưa đăng nhập → Lưu vào session
                $sessionCart = session()->get('cart', []);
                $key = $productId . '-' . ($productVariantId ?? 'default');

                $cartQuantity = isset($sessionCart[$key]) ? $sessionCart[$key]['quantity'] : 0;

                // Kiểm tra nếu tổng số lượng vượt quá tồn kho
                if (($cartQuantity + $quantity) > $availableStock) {
                    return response()->json([
                        'message' => 'Không đủ tồn kho. Chỉ còn ' . $availableStock . ' sản phẩm.'
                    ], 400);
                }

                if (isset($sessionCart[$key])) {
                    $sessionCart[$key]['quantity'] += $quantity;
                } else {
                    $sessionCart[$key] = [
                        'product_id' => $productId,
                        'product_variant_id' => $productVariantId,
                        'quantity' => $quantity,
                        'price' => $price
                    ];
                }

                session()->put('cart', $sessionCart);

                return response()->json([
                    'message' => 'Sản phẩm đã thêm vào giỏ hàng (Session)',
                    'cart_items' => array_values($sessionCart)
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
            // Nếu đã đăng nhập → Cập nhật database
            $cartItem = CartItem::where('product_id', $productId)
                ->where('product_variant_id', $variantId)
                ->where('user_id', $userId)
                ->first();

            if (!$cartItem) {
                return response()->json(['message' => 'Không tìm thấy sản phẩm trong giỏ hàng'], 404);
            }

            // Kiểm tra số lượng tồn kho
            $stock = $variantId ? ProductVariant::where('id', $variantId)->value('stock') : Product::where('id', $productId)->value('stock');

            if ($stock < $request->quantity) {
                return response()->json(['message' => 'Số lượng sản phẩm trong kho không đủ'], 400);
            }

            $cartItem->update(['quantity' => $request->quantity]);
            return response()->json(['message' => 'Cập nhật số lượng thành công']);
        } else {
            // Nếu chưa đăng nhập → Cập nhật session
            $sessionCart = session()->get('cart', []);
            $key = $productId . '-' . ($variantId ?? 'default');

            if (!isset($sessionCart[$key])) {
                return response()->json(['message' => 'Không tìm thấy sản phẩm trong giỏ hàng'], 404);
            }

            // Kiểm tra số lượng tồn kho
            $stock = $variantId ? ProductVariant::where('id', $variantId)->value('stock') : Product::where('id', $productId)->value('stock');

            if ($stock < $request->quantity) {
                return response()->json(['message' => 'Số lượng sản phẩm trong kho không đủ'], 400);
            }

            // Cập nhật số lượng trong session
            $sessionCart[$key]['quantity'] = $request->quantity;
            session()->put('cart', $sessionCart);

            return response()->json(['message' => 'Cập nhật số lượng thành công (Session)']);
        }
    }



    public function destroy(Request $request, $productId, $variantId = null)
    {
        $user = Auth::guard('sanctum')->user();
        $userId = $user ? $user->id : null;

        if ($userId) {
            // 🟢 Nếu đã đăng nhập → Xóa khỏi database
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
            // 🔴 Nếu chưa đăng nhập → Xóa khỏi session
            $sessionCart = session()->get('cart', []);
            $key = $productId . '-' . ($variantId ?? 'default');

            if (!isset($sessionCart[$key])) {
                return response()->json(['message' => 'Không tìm thấy sản phẩm trong giỏ hàng'], 404);
            }

            unset($sessionCart[$key]);
            session()->put('cart', $sessionCart);

            return response()->json(['message' => 'Sản phẩm đã được xóa (Session)']);
        }
    }
}
