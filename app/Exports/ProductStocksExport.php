<?php

namespace App\Exports;

use App\Models\ProductStock;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class ProductStocksExport implements FromCollection, WithHeadings
{
    protected $orderIds;

    public function __construct(array $orderIds = [])
    {
        $this->orderIds = $orderIds;
    }

    public function collection()
    {
        $query = ProductStock::join('products', 'products.id', '=', 'product_stocks.product_id')
            ->join('stocks', 'stocks.id', '=', 'product_stocks.stock_id')
            ->join('attribute_value_product_variants', 'product_stocks.product_variant_id', '=', 'attribute_value_product_variants.product_variant_id')
            ->join('attribute_values', 'attribute_values.id', '=', 'attribute_value_product_variants.attribute_value_id')
            ->select([
                'product_stocks.id',
                DB::raw('products.name as product_name'),
                // Lấy 2 giá trị thuộc tính đầu tiên của biến thể
                DB::raw("SUBSTRING_INDEX(GROUP_CONCAT(attribute_values.value ORDER BY attribute_values.id SEPARATOR ', '), ',', 2) as variant_attributes"),
                'product_stocks.quantity',
                'product_stocks.price',
                DB::raw("CASE 
                            WHEN stocks.status = 0 THEN 'Chưa xác nhận' 
                            WHEN stocks.status = 1 THEN 'Đã xác nhận' 
                            WHEN stocks.status = -1 THEN 'Bị hủy' 
                            ELSE 'Không xác định' 
                         END as stock_status"),
                'product_stocks.stock_id',
                DB::raw("DATE_FORMAT(product_stocks.created_at, '%d-%m-%Y') as formatted_created_at")
            ])
            ->groupBy('product_stocks.id');

        if (!empty($this->orderIds)) {
            $query->whereIn('product_stocks.stock_id', $this->orderIds);
        }

        return $query->get();
    }

    public function headings(): array
    {
        return [
            "ID", 
            "Tên sản phẩm", 
            "Thuộc tính biến thể", 
            "Số lượng", 
            "Giá", 
            "Trạng thái kho", 
            "Mã kho",
            "Ngày nhập",
        ];
    }
}
