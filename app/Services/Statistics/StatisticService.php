<?php

namespace App\Services\Statistics;

use App\Repositories\Statistics\StatisticRepository;
class StatisticService
{
    private $statisticRepository;
    public function __construct(StatisticRepository $statisticRepository)
    {
        $this->statisticRepository = $statisticRepository;
    }
    public function topUserBought(){
        return $this->statisticRepository->topUserBought();
    }
    public function topProductBought($filter){
        return $this->statisticRepository->topProductBought($filter);
    }
    public function revenue(){
        return $this->statisticRepository->revenue();
    }
    public function orderStatistics(){
        return $this->statisticRepository->orderStatistics();
    }
    public function topBuyView(){
        return $this->statisticRepository->topBuyView();
    }
    public function topRevenueDays(){
        return $this->statisticRepository->topRevenueDays();
    }
    public function revenueStatistics(){
        return $this->statisticRepository->revenueStatistics();
    }
    
}