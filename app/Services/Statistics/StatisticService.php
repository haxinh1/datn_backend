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
    
}