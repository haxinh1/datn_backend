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
    public function addViewedProducts($user,$product){
       return $this->productRepository->addViewedProducts($user,$product);
    }
    public function viewedProduct($user){
       return $this->productRepository->viewedProduct($user);
    }
    public function moreViewProductById($user){
       return $this->productRepository->moreViewProductById($user);
    }

}