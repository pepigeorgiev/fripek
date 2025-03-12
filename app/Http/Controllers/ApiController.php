<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ApiController extends Controller
{

    public function getBreadTypePrice($breadTypeId, $companyId)
{
    $breadType = BreadType::find($breadTypeId);
    $company = Company::find($companyId);
    
    if (!$breadType || !$company) {
        return response()->json(['error' => 'Bread type or company not found'], 404);
    }
    
    $date = request('date', now()->toDateString());
    
    // Use the same price calculation logic
    $price = DailyTransaction::calculatePriceForBreadType($breadType, $company, $date);
    
    return response()->json([
        'price' => $price,
        'source' => 'calculated'
    ]);
}
    //
}
