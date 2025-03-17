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

            $totalSpent = Order::where('user_id', $order->user_id)->sum('total_amount');


            $rank = 'Đồng';
            if ($totalSpent >= 20000000) {
                $rank = 'Kim Cương';
            } elseif ($totalSpent >= 10000000) {
                $rank = 'Vàng';
            } elseif ($totalSpent >= 5000000) {
                $rank = 'Bạc';
            } else {
                $rank = 'Đồng';
            }
            $user = User::where('id', $order->user_id)->first();
            if ($user) {
                if ($user->rank !== $rank || $user->total_spent !== $totalSpent) {
                    $user->rank = $rank;
                    $user->total_spent = $totalSpent;
                    $user->save();
                }
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
