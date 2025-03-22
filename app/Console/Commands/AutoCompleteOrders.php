<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Order;
use App\Models\OrderOrderStatus;
use Carbon\Carbon;

class AutoCompleteOrders extends Command
{
    protected $signature = 'orders:auto-complete';
    protected $description = 'Tự động cập nhật trạng thái đơn hàng từ "Đã giao hàng" sang "Hoàn thành" sau 7 ngày';

    public function handle()
    {
        $this->info('Đang kiểm tra đơn hàng cần cập nhật trạng thái...');

        // Lấy tất cả đơn hàng đang ở trạng thái 5 (đã giao hàng) trên 7 ngày
        $orders = Order::where('status_id', 5)
            ->where('updated_at', '<=', Carbon::now()->subDays(7))
            ->get();

        if ($orders->isEmpty()) {
            $this->info('Không có đơn hàng nào cần cập nhật.');
            return;
        }

        foreach ($orders as $order) {
            // Cập nhật trạng thái sang 7 (Hoàn thành)
            $order->update(['status_id' => 7]);

            // Lưu lịch sử trạng thái đơn hàng
            OrderOrderStatus::create([
                'order_id' => $order->id,
                'order_status_id' => 7,
                'modified_by' => null, // hệ thống tự cập nhật, không phải user cụ thể
                'note' => 'Hệ thống tự động cập nhật đơn hàng hoàn thành sau 7 ngày từ ngày giao hàng thành công.'
            ]);

            $this->info('Đã cập nhật đơn hàng #' . $order->id . ' thành "Hoàn thành".');
        }

        $this->info('Hoàn tất cập nhật trạng thái đơn hàng.');
    }
}
