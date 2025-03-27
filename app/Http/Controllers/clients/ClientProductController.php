<?php

namespace App\Http\Controllers\clients;

use App\Http\Controllers\Controller;

use App\Models\ViewedProduct;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Services\Clients\ProductService;

class ClientProductController extends Controller
{
    private $productService;

    public function __construct(ProductService $productService){
        $this->productService = $productService;
    }

    public function productDetail(string $id){
        try {
            $product = $this->productService->showProductById($id);
            // $stocks = $this->productService->getHistoryStockProduct($id);
            $user = Auth::guard('sanctum')->user();
            $dataViewed= [];
            if ($user) {
               $this->productService->addViewedProducts($user,$product);
               $dataViewed = $this->productService->viewedProduct($user);
            }
            
            return response()->json([
                'success' => true,
                'data' => $product,
                // 'stocks' => $stocks,
                'dataViewed' => $dataViewed,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Sản phẩm không tìm thấy hoặc xảy ra lỗi! ' . $e->getMessage(),
            ], 404);
        }
    }
}