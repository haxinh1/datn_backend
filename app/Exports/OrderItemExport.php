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
                WHEN orders.status_id = 1 THEN 'Chờ thanh toán'
                WHEN orders.status_id = 2 THEN 'Đã thanh toán trực tuyến'
                WHEN orders.status_id = 3 THEN 'Đang xử lý'
                WHEN orders.status_id = 4 THEN 'Đang giao hàng'
                WHEN orders.status_id = 5 THEN 'Đã giao hàng'
                WHEN orders.status_id = 6 THEN 'Giao hàng thất bại'
                WHEN orders.status_id = 7 THEN 'Hoàn thành'
                WHEN orders.status_id = 8 THEN 'Hủy đơn'
                WHEN orders.status_id = 9 THEN 'Chờ xử lý trả hàng'
                WHEN orders.status_id = 10 THEN 'Chấp nhận trả hàng'
                WHEN orders.status_id = 11 THEN 'Từ chối trả hàng'
                WHEN orders.status_id = 12 THEN 'Hoàn tiền thành công'
                WHEN orders.status_id = 13 THEN 'Hàng đang quay về shop'
                WHEN orders.status_id = 14 THEN 'Người bán đã nhận hàng'
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
