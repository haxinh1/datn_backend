<?php

namespace App\Http\Controllers\clients;

use App\Http\Controllers\Controller;

use App\Models\ViewedProduct;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Services\Clients\ProductService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ClientProductController extends Controller
{
    private $productService;

    public function __construct(ProductService $productService){
        $this->productService = $productService;
    }

    public function productDetail(string $id){
        try { 
            $product = $this->productService->showProductById($id);
            $user = Auth::guard('sanctum')->user();
            $dataViewed= [];
        
            if ($user) {
                $this->productService->addViewedProducts($user, $product);
                $dataViewed = $this->productService->viewedProduct($user);
            }
            $recommended_products = [];
            try {
                $response = Http::get('http://127.0.0.1:5000/recommend', [
                    'product_id' => $id
                ]);
        
                if ($response->successful()) {
                    $data = $response->json();
                    $recommended_products = $data['recommended_products'] ?? [];
                }
            } catch (\Exception $e) {
                // Ghi log lỗi nếu cần
                Log::error('Lỗi gọi API gợi ý sản phẩm: ' . $e->getMessage());
            }
        
            return response()->json([
                'success' => true,
                'data' => $product,
                'dataViewed' => $dataViewed,
                'recommended_products' => $recommended_products,
            ], 200);
        
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Sản phẩm không tìm thấy hoặc xảy ra lỗi! ' . $e->getMessage(),
            ], 404);
        }
        
    }
}