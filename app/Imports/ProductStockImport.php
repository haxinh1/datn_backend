<?php

namespace App\Imports;

use App\Models\Product;
use App\Models\Stock;
use App\Models\ProductStock;
use App\Models\ProductVariant;
use App\Models\AttributeValue;
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
            $groupedProducts = [];

            foreach ($rows as $index => $row) {
                if ($index == 0) continue; // Bỏ qua dòng tiêu đề

                $productName = trim($row[0]);
                $attribute1 = trim($row[1]);
                $attribute2 = trim($row[2]);

                $quantity = (int) $row[3];
                $price = (float) $row[4];

                // Tìm sản phẩm theo tên
                $product = Product::where('name', $productName)->first();
                if (!$product) {
                    $this->errors[] = "Không tìm thấy sản phẩm với tên: {$productName}";
                    continue;
                }

                // Chuẩn bị mảng các giá trị thuộc tính cần tìm
                $attributesToSearch = [];
                if (!empty($attribute1)) {
                    $attributesToSearch[] = $attribute1;
                }
                if (!empty($attribute2)) {
                    $attributesToSearch[] = $attribute2;
                }
                
                // Số lượng thuộc tính cần kiểm tra
                $attributeCount = count($attributesToSearch);
                
                // Tìm các giá trị thuộc tính nếu có
                $attributeValues = [];
                if ($attributeCount > 0) {
                    $attributeValues = AttributeValue::whereIn('value', $attributesToSearch)->pluck('id')->toArray();
                    
                    // Kiểm tra xem có tìm thấy đủ số lượng thuộc tính không
                    if (count($attributeValues) < $attributeCount) {
                        $this->errors[] = "Không tìm thấy đủ thuộc tính cho sản phẩm: {$productName}";
                        continue;
                    }
                }
                
                // Tìm biến thể phù hợp với các attribute value
                if ($attributeCount > 0) {
                    // Nếu có thuộc tính, tìm biến thể phù hợp
                    $variantQuery = ProductVariant::where('product_id', $product->id)
                        ->whereHas('attributeValues', function ($q) use ($attributeValues) {
                            $q->whereIn('attribute_values.id', $attributeValues);
                        }, '=', $attributeCount); // Số lượng thuộc tính chính xác
                    
                    $productVariant = $variantQuery->first();
                    
                    if (!$productVariant) {
                        $this->errors[] = "Không tìm thấy biến thể phù hợp cho sản phẩm: {$productName}";
                        continue;
                    }
                } else {
                    // Nếu không có thuộc tính, tìm biến thể mặc định hoặc sản phẩm không có biến thể
                    $productVariant = ProductVariant::where('product_id', $product->id)
                        ->whereDoesntHave('attributeValues')
                        ->first();
                    
                    // Nếu không tìm thấy biến thể không có thuộc tính
                    if (!$productVariant) {
                        // Tìm biến thể đầu tiên của sản phẩm
                        $productVariant = ProductVariant::where('product_id', $product->id)->first();
                        
                        // Nếu vẫn không có biến thể nào
                        if (!$productVariant) {
                            // Tạo một biến thể giả cho sản phẩm không có biến thể
                            $productVariant = new \stdClass();
                            $productVariant->id = null;
                            $productVariant->sell_price = $product->price ?? 0;
                        }
                    }
                }

                // Kiểm tra giá nhập
                // Kiểm tra giá nhập nếu có giá bán
                if (isset($productVariant->sell_price) && $productVariant->sell_price > 0) {
                    if ($price > $productVariant->sell_price) {
                        $this->errors[] = "Giá nhập của sản phẩm {$productName} cao hơn giá bán ra!";
                        continue;
                    }
                } else if (isset($product->price) && $product->price > 0) {
                    // Nếu không có giá bán của biến thể, kiểm tra với giá sản phẩm
                    if ($price > $product->price) {
                        $this->errors[] = "Giá nhập của sản phẩm {$productName} cao hơn giá bán ra!";
                        continue;
                    }
                }
                // Nếu không có giá bán nào được thiết lập, bỏ qua việc kiểm tra

                // Tạo key nhóm sản phẩm (xử lý trường hợp productVariant->id có thể null)
                $variantId = $productVariant->id ?? 'default';
                $key = $product->id . '-' . $variantId . '-' . $price;

                if (isset($groupedProducts[$key])) {
                    $groupedProducts[$key]['quantity'] += $quantity;
                } else {
                    $groupedProducts[$key] = [
                        'product_id' => $product->id,
                        'product_variant_id' => $productVariant->id ?? null,
                        'quantity' => $quantity,
                        'price' => $price,
                    ];
                }
            }

            // Lưu dữ liệu
            foreach ($groupedProducts as $data) {
                ProductStock::create([
                    'stock_id' => $stock->id,
                    'product_id' => $data['product_id'],
                    'product_variant_id' => $data['product_variant_id'],
                    'quantity' => $data['quantity'],
                    'price' => $data['price'],
                ]);

                $totalAmount += $data['quantity'] * $data['price'];
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