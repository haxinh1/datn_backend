<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

use App\Console\Commands\ClearExpiredSale;
use App\Console\Commands\ClearExpiredCoupon;

class Kernel extends ConsoleKernel
{
    /**
     * Đăng ký các command cho Artisan.
     */
    protected $commands = [
        ClearExpiredSale::class,
        ClearExpiredCoupon::class,
    ];

    /**
     * Lên lịch cho các command Artisan.
     */
    protected function schedule(Schedule $schedule): void
    {
        // Xóa giá sale sản phẩm hết hạn mỗi phút
        $schedule->command('clear:expired-sale')
            ->everyMinute()
            ->withoutOverlapping();

        // Xóa coupon hết hạn mỗi phút
        $schedule->command('clear:expired-coupon')
            ->everyMinute()
            ->withoutOverlapping();
    }

    /**
     * Load các command tự động.
     */
    protected function commands(): void
    {
        $this->load(__DIR__ . '/Commands');
        require base_path('routes/console.php');
    }
}
