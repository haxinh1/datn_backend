<?php

namespace App\Imports;

use App\Models\ProductStock;
use Maatwebsite\Excel\Concerns\ToModel;

class ProductStockImport implements ToModel
{
    /**
    * @param array $row
    *
    * @return \Illuminate\Database\Eloquent\Model|null
    */
    public function model(array $row)
    {
        return new ProductStock([
            //
        ]);
    }
}
