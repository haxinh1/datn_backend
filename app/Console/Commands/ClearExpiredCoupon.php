<?php

namespace App\Console\Commands;

use App\Models\Coupon;
use Carbon\Carbon;
use Illuminate\Console\Command;

class ClearExpiredCoupon extends Command
{
    protected $signature = 'clear:expired-coupon';

    protected $description = '✅ Xóa tất cả coupon đã hết hạn.';

    public function handle()
    {
        $now = Carbon::now();

        // Đúng field trong bảng coupons là end_date
        $expiredCoupons = Coupon::whereNotNull('end_date')
            ->where('end_date', '<', $now);

        $count = $expiredCoupons->count();

        if ($count > 0) {
            $expiredCoupons->delete(); 
            $this->info("✅ Đã xóa {$count} coupon hết hạn!");
        } else {
            $this->info("🔔 Không có coupon nào hết hạn cần xóa.");
        }
    }
}
