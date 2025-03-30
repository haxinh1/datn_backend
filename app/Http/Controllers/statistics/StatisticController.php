<?php

namespace App\Http\Controllers\statistics;

use App\Http\Controllers\Controller;
use App\Services\Statistics\StatisticService;
use Illuminate\Http\Request;

class StatisticController extends Controller
{
    protected $statisticService;
    public function __construct(StatisticService $statisticService){
        $this->statisticService = $statisticService;
    }
    public function topUserBought(){

        $topUserBought = $this->statisticService->topUserBought();

        return response()->json([
            'topUserBought'=> $topUserBought,
        ]);
    }
    public function topProductBought(Request $request){
        $filter = 'quantity';
        if($request->has('filter')){
            $filter = $request->input('filter');
        }
        $topProductBought = $this->statisticService->topProductBought($filter);
        return response()->json([
            'topProductBought'=> $topProductBought,
        ]);
    }
    public function revenue(Request $request){
        $revenue = $this->statisticService->revenue();
        return response()->json($revenue);
    }
    public function orderStatistics(Request $request){
        $orderStatistics = $this->statisticService->orderStatistics();
        return response()->json($orderStatistics);
    }
    public function topBuyView(Request $request){
        $topBuyView = $this->statisticService->topBuyView();
        return response()->json($topBuyView);
    }
    public function revenueStatistics(Request $request){
        $revenueStatistics = $this->statisticService->revenueStatistics();
        return response()->json($revenueStatistics);
    }
    public function topRevenueDays(Request $request){
        $topRevenueDays = $this->statisticService->topRevenueDays();
        return response()->json($topRevenueDays);
    }

}
