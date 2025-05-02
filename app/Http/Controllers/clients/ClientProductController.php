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

    public function productDetail(string $id)
    {
        try {
            $product = $this->productService->showProductById($id);
            $this->productService->moreViewProductById($id);
    
            $user = Auth::guard('sanctum')->user();
            $dataViewed = [];
    
            if ($user) {
                $this->productService->addViewedProducts($user, $product);
                $dataViewed = $this->productService->viewedProduct($user);
            }

            $avgRate = $this->productService->avgRate($id);
    
            return response()->json([
                'success' => true,
                'data' => $product,
                'avgRate' => $avgRate,
                'dataViewed' => $dataViewed,
            ], 200);
    
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Sản phẩm không tìm thấy hoặc xảy ra lỗi! ' . $e->getMessage(),
            ], 404);
        }
    }
    public function getRecommendedProducts($id)
    {
        $recommended_products = [];
    
        try {
            $response = Http::timeout(2)->get('http://127.0.0.1:5000/recommend', [
                'product_id' => $id
            ]);
    
            if ($response->successful()) {
                $data = $response->json();
                $recommended_products = $data['recommended_products'] ?? [];
            }
        } catch (\Throwable $e) {
            Log::warning('API gợi ý sản phẩm lỗi hoặc quá chậm: ' . $e->getMessage());
        }
    
        return response()->json([
            'success' => true,
            'recommended_products' => $recommended_products,
        ]);
    }
    public function searchImage(Request $request)
    {
        if (!$request->hasFile('image')) {
            return response()->json(['error' => 'Vui lòng chọn ảnh để tìm kiếm'], 400);
        }

        $image = $request->file('image');

        try {
            $response = Http::attach(
                'image',      
                file_get_contents($image),
                $image->getClientOriginalName()
            )->post('http://127.0.0.1:5000/search-image');

            if ($response->successful()) {
                return response()->json($response->json());
            } else {
                return response()->json(['error' => 'Lỗi từ API Flask', 'detail' => $response->body()], $response->status());
            }

        } catch (\Exception $e) {
            return response()->json(['error' => 'Không thể kết nối đến API Flask', 'message' => $e->getMessage()], 500);
        }
    }        
}