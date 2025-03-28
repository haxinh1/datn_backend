<?php

namespace App\Repositories\Statistics;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class StatisticRepository
{
    protected $user;
    protected $order;
    protected $orderItems;

    public function __construct(User $user, Order $order, OrderItem $orderItems)
    {
        $this->user = $user;
        $this->order = $order;
        $this->orderItems = $orderItems;
    }

    public function topUserBought()
    {
        $datas = $this->orderItems->newQuery()
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->join('users', 'orders.user_id', '=', 'users.id')
            ->select(
                'users.fullname',
                'users.gender',
                'users.phone_number',
                'users.rank',
                'users.avatar',
                DB::raw('SUM(order_items.quantity) as total_quantity'),
                DB::raw('SUM(orders.total_amount) as total_amount')
            )
            ->groupBy('users.id', 'users.fullname', 'users.gender', 'users.phone_number', 'users.rank', 'users.avatar')
            ->orderByDesc('total_quantity')
            ->where('orders.status_id', '=', 13)
            ->limit(10)
            ->get();

        return $datas;
    }
    public function topProductBought($filter)
    {
        $datas = $this->orderItems->newQuery()
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->join('products', 'order_items.product_id', '=', 'products.id')
            ->select(
                'products.id',
                'products.name',
                'products.thumbnail',
                'products.sell_price',
                'products.sale_price',
                DB::raw('SUM(order_items.quantity) as quantity'),
                DB::raw('SUM(orders.total_amount) as total_amount')
            )
            ->where('orders.status_id', '=', 13)
            ->groupBy('products.id', 'products.name', 'products.thumbnail', 'products.sell_price', 'products.sale_price')
            ->orderByDesc($filter)
            ->limit(10)
            ->get();


        return $datas;
    }
}
