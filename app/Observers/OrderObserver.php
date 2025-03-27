<?php

namespace App\Observers;

use App\Models\Order;
use App\Models\User;

class OrderObserver
{

    /**
     * Handle the Order "saved" event.
     *
     * @param \App\Models\Order $order
     * @return void
     */

    public function saved(Order $order)
    { 
        
        if ($order->status_id == 7) {
    
            $point = ($order->total_amount * 2) / 100;

            $rankPoints = ($order->total_amount * 2) / 100;
    
            // Lấy user
            $user = User::where('id', $order->user_id)->first();
    
            if ($user) {
                // Cộng điểm mới vào điểm hiện tại
                $user->loyalty_points += $point;

                $user->rank_points += $rankPoints;
    
                // Cộng thêm số tiền đơn hàng vào tổng đã chi
                $user->total_spent += $order->total_amount;
    
                // Cập nhật hạng theo tổng đã chi mới
                $rankPoints = $user->rank_points;
                $rank = 'Thành Viên';
    
                if ($rankPoints >= 400000) {
                    $rank = 'Kim Cương';
                } elseif ($rankPoints >= 200000) {
                    $rank = 'Vàng';
                } elseif ($rankPoints >= 100000) {
                    $rank = 'Bạc';
                } elseif ($rankPoints >= 40000) {
                    $rank = 'Đồng';
                }
    
                $user->rank = $rank;
                $user->save();
            }
      
    }
    }

    public function created(Order $order): void
    {
        //
    }

    /**
     * Handle the Order "updated" event.
     */
    public function updated(Order $order): void
    {
        //
    }

    /**
     * Handle the Order "deleted" event.
     */
    public function deleted(Order $order): void
    {
        //
    }

    /**
     * Handle the Order "restored" event.
     */
    public function restored(Order $order): void
    {
        //
    }

    /**
     * Handle the Order "force deleted" event.
     */
    public function forceDeleted(Order $order): void
    {
        //
    }
}
