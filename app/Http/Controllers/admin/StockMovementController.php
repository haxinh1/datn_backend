<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;

use App\Models\StockMovement;
use App\Http\Requests\StoreStockMovementRequest;
use App\Http\Requests\UpdateStockMovementRequest;

class StockMovementController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //gegege
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreStockMovementRequest $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(StockMovement $StockMovement)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(StockMovement $StockMovement)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateStockMovementRequest $request, StockMovement $StockMovement)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(StockMovement $StockMovement)
    {
        //
    }
}
