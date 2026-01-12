<?php

namespace App\Http\Controllers\Admin\Modules\Production;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\TentativeItem;
use App\Models\ProductionProduct;
use Illuminate\Support\Facades\DB;

class TentativeItemController extends Controller
{
    public function storeAndUpdate(Request $request)
    {
        try {
            $validated = $request->validate([
                'pp_id'       => ['required', 'integer', 'exists:production_products,id'],
                'material_id' => ['required', 'array', 'min:1'],
                'material_id.*' => ['required', 'integer', 'exists:materials,id'],
                'qty'         => ['required', 'array', 'min:1'],
                'qty.*'       => ['required', 'numeric', 'min:1'],
            ]);
    
            if (count($validated['material_id']) !== count($validated['qty'])) {
                return response()->json([
                    'success' => false,
                    'error'   => 'Material ID and Qty count mismatch'
                ], 422);
            }
    
            DB::beginTransaction();
    
            $pp = ProductionProduct::findOrFail($validated['pp_id']);
    
            
            $product_id = $pp->product_id;
    
            
            TentativeItem::where('product_id', $product_id)->delete();
    
            
            $insertData = [];
            foreach ($validated['material_id'] as $index => $materialId) {
                $insertData[] = [
                    'product_id'  => $product_id,
                    'material_id' => $materialId,
                    'qty'         => $validated['qty'][$index],
                    'created_at'  => now(),
                    'updated_at'  => now(),
                ];
            }
    
            TentativeItem::insert($insertData);
    
            DB::commit();
    
            return response()->json([
                'success' => true,
                'message' => 'Tentative items updated successfully',
                'data' => $insertData
            ]);
    
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'error' => 'Failed to update items',
                'message' => $e->getMessage(),
            ], 500);
        }
    }



}
