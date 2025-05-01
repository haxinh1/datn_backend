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
        // Truy vấn chính
        $query = ProductStock::join('products', 'products.id', '=', 'product_stocks.product_id')
            ->join('stocks', 'stocks.id', '=', 'product_stocks.stock_id')
            ->leftJoin('product_variants', 'product_stocks.product_variant_id', '=', 'product_variants.id')
            ->select([
                'product_stocks.id',
                DB::raw('products.name as product_name'),
                DB::raw('(CASE WHEN product_stocks.product_variant_id IS NULL THEN "" ELSE 
                    (SELECT GROUP_CONCAT(attribute_values.value ORDER BY attribute_values.id SEPARATOR ", ")
                    FROM attribute_value_product_variants
                    JOIN attribute_values ON attribute_values.id = attribute_value_product_variants.attribute_value_id
                    WHERE attribute_value_product_variants.product_variant_id = product_stocks.product_variant_id
                    GROUP BY attribute_value_product_variants.product_variant_id)
                END) as variant_attributes'),
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
            ]);

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