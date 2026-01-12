<?php

namespace App\Http\Controllers\Admin\Modules\Production;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ProductionProduct;
use App\Models\EmployeeWorksheet;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;

class EmployeeWorksheetController extends Controller
{
    public function storeWorksheet(Request $request)
    {
        try {
            
            $validated = $request->validate([
                'pp_id'        => 'required|integer|exists:production_products,id',
                'labour_id'    => 'required|array|min:1',
                'labour_id.*'  => 'required|integer|exists:labours,id',
    
                'date'         => 'required|array|min:1',
                'date.*'       => 'required|date',
            ]);
    
            DB::beginTransaction();
    
            $insertData = [];
    
            foreach ($validated['labour_id'] as $key => $labourId) {
                $insertData[] = [
                    'pp_id'     => $validated['pp_id'],
                    'labour_id' => $labourId,
                    'date'      => $validated['date'][$key],
                    'overtime'      => $request->overtime[$key],
                    'created_at'=> now(),
                    'updated_at'=> now(),
                ];
            }
    
            
            EmployeeWorksheet::insert($insertData);
    
            
            $stored = EmployeeWorksheet::where('pp_id', $validated['pp_id'])
                ->whereIn('labour_id', $validated['labour_id'])
                ->orderBy('id', 'desc')
                ->get();
    
            DB::commit();
    
            return response()->json([
                'message' => 'Worksheet stored successfully.',
                'data'    => $stored,  
            ], 200);
    
        } catch (\Exception $e) {
            DB::rollBack();
    
            return response()->json([
                'error'   => 'Failed to store worksheet.',
                'details' => $e->getMessage(),
            ], 500);
        }
    }
    
    
    public function getAllWorksheet(Request $request)
    {
        try {
            $validated = $request->validate([
                'pp_id' => 'required|integer|exists:production_products,id',
            ]);
    
            $data = EmployeeWorksheet::with('labour')->where('pp_id', $validated['pp_id'])
                        ->orderBy('id', 'desc')
                        ->get();
    
            return response()->json([
                'success' => true,
                'data'    => $data
            ], 200);
    
        } catch (\Exception $e) {
    
            return response()->json([
                'error'   => 'Failed to fetch worksheet.',
                'details' => $e->getMessage(),
            ], 500);
        }
    }



}
