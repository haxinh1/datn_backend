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
            'success'=>true,
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
            'success'=>true,
            'topProductBought'=> $topProductBought,
        ]);
    }

}
