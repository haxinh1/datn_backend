<?php 
namespace App\Repositories\Clients;
use App\Models\Product;
use App\Models\ViewedProduct;
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
    public function moreViewProductById($id){
        $data = Product::find($id);
        $data->increment('views');
        return $data;
    }
    public function getHistoryStockProduct($id){

        $datas = DB::table('product_stocks')
        ->leftJoin('products', 'product_stocks.product_id', '=', 'products.id')
        ->leftJoin('product_variants', 'product_stocks.product_variant_id', '=', 'product_variants.id')
        ->leftJoin('stocks', 'product_stocks.stock_id', '=', 'stocks.id') 
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
        ->where('stocks.status', 1) 
        ->get();
        return $datas;
    }

    public function addViewedProducts($user,$product){
        $exists = ViewedProduct::where('user_id', $user->id)
        ->where('product_id', $product->id)
        ->exists();
    
        if (!$exists) {
            $count = ViewedProduct::where('user_id', $user->id)->count();
            if ($count >= 8) {
                ViewedProduct::where('user_id', $user->id)
                    ->orderBy('created_at', 'asc') 
                    ->limit(1)
                    ->delete();
            }
        
            ViewedProduct::create([
                'user_id' => $user->id,
                'product_id' => $product->id,
            ]);
        }
    }
    public function viewedProduct($user){
        $datas = ViewedProduct::where('user_id', $user->id)
        ->orderBy('created_at', 'desc')
        ->with(['product' => function ($query) {
            $query->select('id', 'name', 'slug', 'thumbnail', 'sell_price', 'sale_price');
        }])
        ->get()
        ->pluck('product');;
        return $datas;
    }
    public function avgRate($id)
{
    $product = Product::withAvg(['comments as comments_avg_rating' => function ($query) {
                            $query->where('status', 1);
                        }], 'rating')
                      ->withCount(['comments as comments_count' => function ($query) {
                            $query->where('status', 1);
                        }])
                      ->find($id);

    if (!$product) {
        return [
            'avg' => 5,
            'total' => 0
        ];
    }

    $avg = round($product->comments_avg_rating, 1);

    return [
        'avg' => $avg == 0 ? 5 : $avg,
        'total' => $product->comments_count
    ];
}






}