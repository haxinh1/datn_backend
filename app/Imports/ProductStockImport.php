<?php

namespace App\Imports;

use App\Models\ProductStock;
use App\Models\Stock;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\ToCollection;
use Illuminate\Support\Collection;

class ProductStockImport implements ToCollection
{
    public $errors = [];

    public function collection(Collection $rows)
    {
        DB::beginTransaction();
        try {
            $stock = Stock::create([
                'total_amount' => 0,
                'status' => 0,
            ]);
    
            $totalAmount = 0;
    
            foreach ($rows as $index => $row) {
                if ($index == 0) continue; // Bỏ qua dòng tiêu đề
                
                $product = Product::find($row[0]);
                if (!$product) {
                    $this->errors[] = "Không tìm thấy sản phẩm với ID: {$row[0]}";
                    continue;
                }

                $productVariant = null;
                if (!empty($row[1])) {
                    $productVariant = ProductVariant::find($row[1]);
                    if (!$productVariant) {
                        $this->errors[] = "Không tìm thấy biến thể sản phẩm với ID: {$row[1]}";
                        continue;
                    }
                }
    
                $quantity = (int) $row[2];
                $price = (float) $row[3];
                
                if ($productVariant && $price > $productVariant->sell_price) {
                    $this->errors[] = "Giá nhập của biến thể sản phẩm ID: {$row[1]} cao hơn giá bán ra!";
                    continue;
                } elseif (!$productVariant && $price > $product->sell_price) {
                    $this->errors[] = "Giá nhập của sản phẩm ID: {$row[0]} cao hơn giá bán ra!";
                    continue;
                }
    
                ProductStock::create([
                    'stock_id' => $stock->id,
                    'product_id' => $product->id,
                    'product_variant_id' => $productVariant ? $productVariant->id : null,
                    'quantity' => $quantity,
                    'price' => $price,
                ]);
                
                $totalAmount += $quantity * $price;
            }
    
            $stock->update(['total_amount' => $totalAmount]);
    
            if (!empty($this->errors)) {
                DB::rollBack();
                return;
            }
    
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            $this->errors[] = "Lỗi hệ thống: " . $e->getMessage();
        }
    }
}
