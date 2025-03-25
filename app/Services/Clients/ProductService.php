<?php

namespace App\Services\Clients;

use App\Repositories\Clients\ProductRepository;
class ProductService
{
    private $productRepository;
    public function __construct(ProductRepository $productRepository)
    {
        $this->productRepository = $productRepository;
    }
    public function showProductById($id){
       return $this->productRepository->showProductById($id);
    }
    public function getHistoryStockProduct($id){
       return $this->productRepository->getHistoryStockProduct($id);
    }

}