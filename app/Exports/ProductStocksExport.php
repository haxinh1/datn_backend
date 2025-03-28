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
            ->select([
                'product_stocks.id',
                DB::raw('products.name as product_name'),
                'product_stocks.product_variant_id',
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
    
        // Nếu có danh sách order_ids, lọc theo `stocks.order_id`
        if (!empty($this->orderIds)) {
            $query->whereIn('product_stocks.stock_id', $this->orderIds);
        }
    
        return $query->get();
    }

    /**
     * Định nghĩa tiêu đề cho các cột trong file Excel.
     */
    public function headings(): array
    {
        return [
            "ID", 
            "Tên sản phẩm", 
            "Id biến thể", 
            "Số lượng", 
            "Giá", 
            "Trạng thái kho", 
            "Mã kho",
            "Ngày nhập",
        ];
    }
}
