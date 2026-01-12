<?php

namespace App\Http\Controllers\Admin\Modules\Production;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ProductionOrder;
use App\Models\ProductionProduct;
use App\Models\Product;
use App\Models\ProductionLog;
use App\Models\Department;
use App\Models\MaterialRequest;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;


class ManageReadyProductController extends Controller
{
    public function getReadyProduct(Request $request)
    {
        try {
            $query = ProductionOrder::with(['customer','products.rrp', 'products' => function ($q) {
                    $q->where('status', 2); 
                }])
                // ->whereHas('products', function ($q) {
                //     $q->where('status', 2);
                // })
                ->orderByDesc('id');
    
            // if ($request->boolean('ownData')) {
            //     $query->whereNull('quotation_id');
            // } else {
            //     $query->whereNotNull('quotation_id');
            // }
    
            $orders = $query->paginate($request->input('limit', 10));
    
            return response()->json([
                'success' => true,
                'data' => $orders->items(),
                'total' => $orders->total(),
                'current_page' => $orders->currentPage(),
                'last_page' => $orders->lastPage(),
            ]);
    
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to fetch ready production orders',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
    
    public function getChallanById(Request $request)
    {
        
        try {
             $validated = $request->validate([
                'id' => 'required|integer|exists:production_orders,id',
            ]);
            $po_id = $request->id;
    
            $batch = ProductionOrder::with([
                'customer',
                'customer.state',
                'products' => function ($q) {
                    $q->where('status', 2);
                }
            ])->find($po_id);
    
            if (!$batch) {
                return response()->json([
                    'success' => false,
                    'error'   => 'Production order not found',
                ], 404);
            }
    
            return response()->json([
                'success' => true,
                'data'    => $batch,
            ]);
    
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error'   => 'Failed to fetch challan data',
                'message' => $e->getMessage(),
            ], 500);
        }
    }


}