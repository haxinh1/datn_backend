<?php

namespace App\Exports;

use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class OrderItemExport implements FromCollection, WithHeadings
{
    protected $orderIds;
    public function __construct(array $orderIds = [])
    {
        $this->orderIds = $orderIds;
    }

    public function collection()
    {
        $query = OrderItem::join('orders', 'order_items.order_id', '=', 'orders.id')
            ->join('products', 'products.id', '=', 'order_items.product_id')
            ->select([
                'order_items.order_id',
                'orders.fullname',
                'orders.phone_number',
                'orders.address',
                DB::raw('products.name as product_name'),
                'order_items.quantity',
                'order_items.sell_price',
                DB::raw("CASE 
                        WHEN orders.status_id = 0 THEN 'Chưa xác nhận' 
                        WHEN orders.status_id = 1 THEN 'Đã xác nhận' 
                        WHEN orders.status_id = -1 THEN 'Bị hủy' 
                        ELSE 'Không xác định' 
                     END as order_status"),
                DB::raw("DATE_FORMAT(order_items.created_at, '%d-%m-%Y') as formatted_created_at")
            ]);

        if (!empty($this->orderIds)) {
            $query->whereIn('order_items.order_id', $this->orderIds);
        }

        return $query->get();
    }
    public function headings(): array
    {
        return [
            "ID đơn hàng",
            "Tên khách hàng",
            "Số điện thoại",
            "Địa chỉ",
            "Sản phẩm",
            "Số lượng",
            "Tổng",
            "Trạng thái đơn hàng",
            "Ngày tạo",
        ];
    }
}
