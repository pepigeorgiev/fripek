<?php

namespace App\Http\Controllers;

use App\Models\BreadType;
use App\Models\BreadTypeOrder;
use Illuminate\Http\Request;

class BreadTypeOrderController extends Controller
{
    public function index()
    {
        // Find the maximum display order to properly position new bread types
        $maxOrder = BreadTypeOrder::max('display_order') ?: 0;
        
        $breadTypes = BreadType::leftJoin('bread_type_order', 'bread_types.id', '=', 'bread_type_order.bread_type_id')
            ->select('bread_types.*', 'bread_type_order.display_order')
            ->orderBy('bread_type_order.display_order', 'asc')
            ->orderBy('bread_types.name', 'asc')
            ->get();
        
        // Set a default display order for new bread types that don't have an order yet
        foreach ($breadTypes as $key => $breadType) {
            if ($breadType->display_order === null) {
                // Assign a position after the highest existing one
                $maxOrder++;
                $breadType->display_order = $maxOrder;
                
                // Save this default position to the database
                BreadTypeOrder::updateOrCreate(
                    ['bread_type_id' => $breadType->id],
                    ['display_order' => $maxOrder]
                );
            }
        }
        
        // Re-sort the collection after assigning default orders
        $breadTypes = $breadTypes->sortBy('display_order');
            
        return view('bread-types.order', compact('breadTypes'));
    }

    public function update(Request $request)
    {
        // Get submitted order values
        $submittedOrders = $request->input('order', []);
        
        // Get current orders from the database
        $currentOrders = [];
        $breadTypes = BreadType::leftJoin('bread_type_order', 'bread_types.id', '=', 'bread_type_order.bread_type_id')
            ->select('bread_types.id', 'bread_type_order.display_order')
            ->get();
            
        foreach ($breadTypes as $breadType) {
            $currentOrders[$breadType->id] = $breadType->display_order ?? 999;
        }
        
        // First, identify which items were changed and which position they should go to
        $changedItems = [];
        foreach ($submittedOrders as $id => $newPosition) {
            $currentPosition = $currentOrders[$id] ?? 999;
            if ((int)$newPosition !== (int)$currentPosition) {
                $changedItems[$id] = [
                    'from' => (int)$currentPosition,
                    'to' => (int)$newPosition
                ];
            }
        }
        
        // If there are changes, handle them
        if (!empty($changedItems)) {
            // Create a position map for all items based on their current order
            $positionMap = $currentOrders;
            
            // Sort changed items by target position to process low positions first
            uasort($changedItems, function($a, $b) {
                return $a['to'] - $b['to'];
            });
            
            // Process each changed item
            foreach ($changedItems as $id => $change) {
                $targetPosition = $change['to'];
                $currentPosition = $change['from'];
                
                // Remove the item from its current position
                $positionMap[$id] = null;
                
                // Create a new position array with the item at its new position
                $newPositionMap = [];
                $counter = 1;
                
                // Process all positions
                for ($i = 1; $i <= count($positionMap); $i++) {
                    // If we're at the target position, insert our item
                    if ($i === $targetPosition) {
                        $newPositionMap[$id] = $counter++;
                    }
                    
                    // Insert other items in order
                    foreach ($positionMap as $itemId => $pos) {
                        if ($pos === $i) {
                            $newPositionMap[$itemId] = $counter++;
                        }
                    }
                }
                
                // Update the position map for next processing
                $positionMap = $newPositionMap;
            }
            
            // Make sure all items have a position (handle any edge cases)
            $counter = 1;
            foreach ($positionMap as $id => $position) {
                if ($position === null) {
                    $positionMap[$id] = $counter++;
                }
            }
            
            // Update with our recalculated positions
            $submittedOrders = $positionMap;
        }
        
        // Save the orders to database
        foreach ($submittedOrders as $breadTypeId => $order) {
            BreadTypeOrder::updateOrCreate(
                ['bread_type_id' => $breadTypeId],
                ['display_order' => $order]
            );
        }
        
        return redirect()->route('bread-types.order.index')
            ->with('success', 'Листата е успешно ажурирана');
    }
}