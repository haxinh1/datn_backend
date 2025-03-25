<?php 
namespace App\Repositories\Clients;
use App\Models\Product;
use Illuminate\Support\Facades\DB;

class ProductRepository {
    public function showProductById($id){
        $data = Product::with([
            'categories',
            'galleries',
            'atributeValueProduct.attributeValue',
            'variants',
            'variants.attributeValueProductVariants.attributeValue',
        ])->where('id', $id)->firstOrFail();
        return $data;
    }
    public function getHistoryStockProduct($id){
        $datas = DB::table('product_stocks')
        ->leftJoin('products', 'product_stocks.product_id', '=', 'products.id')
        ->leftJoin('product_variants', 'product_stocks.product_variant_id', '=', 'product_variants.id')
        ->leftJoin('stocks', 'product_stocks.stock_id', '=', 'stocks.id') // Join bảng stocks
        ->select([
            'product_stocks.id',
            'products.name as product_name',
            'products.thumbnail as product_thumbnail',
            'product_stocks.quantity',
            'product_stocks.price',
            'product_variants.id as product_variant_id',
            'product_variants.sku as variant_sku',
            'product_variants.thumbnail as variant_image',
           'product_stocks.created_at'
        ])
        ->where('product_stocks.product_id', $id)
        ->where('stocks.status', 1) // Chỉ lấy sản phẩm có stock đã xác nhận
        ->get();
        return $datas;
    }

}