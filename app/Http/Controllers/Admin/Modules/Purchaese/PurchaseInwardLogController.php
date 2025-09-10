<?php

namespace App\Http\Controllers\Admin\Modules\Purchaese;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\PurchaseInwardLog;
use Illuminate\Validation\Rule;

class PurchaseInwardLogController extends Controller
{
    public function getData(Request $request)
    {
        try{
            $logs = PurchaseInwardLog::with('purchaseOrder','material')->orderBy('id','desc')->paginate(10);
            return response()->json($logs);
        }catch(\Exception $e){
            return response()->json(['error' => 'Failed to fetch Purchase inward logs'], 500);
        }
        
    }

    public function search(Request $request)
    {
        try {
            $query = PurchaseInwardLog::with('purchaseOrder','material')->orderBy('id', 'desc');

            if ($request->filled('purchase_no')) {
                $query->whereHas('purchaseOrder', function ($q) use ($request) {
                    $q->where('purchase_no', 'ILIKE', '%' . $request->purchase_no . '%');
                });
            }

            if ($request->filled('material')) {
                $query->whereHas('material', function ($q) use ($request) {
                    $q->where('name', 'ILIKE', '%' . $request->material . '%');
                });
            }

            if ($request->filled('qty')) {
                $query->where('qty', 'ILIKE', '%' . $request->qty . '%');
            }

            if ($request->filled('price')) {
                $query->where('price', 'ILIKE', '%' . $request->price . '%');
            }

            if ($request->filled('rate')) {
                $query->where('rate', 'ILIKE', '%' . $request->rate . '%');
            }

            if ($request->filled('invoice_no')) {
                $query->where('invoice_no', 'ILIKE', '%' . $request->invoice_no . '%');
            }

            // Search by Status
            if ($request->has('status') && $request->status !== null) {
                $query->where('status', $request->status);
            }

            $purchaseOrders = $query->paginate(10);

            return response()->json($purchaseOrders);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to fetch purchase orders',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function store(Request $request)
    {
        try{
            
            $request->validate([
                'purchase_order_id' => 'required|exists:purchase_orders,id',
                'material_id'       => 'required|exists:materials,id',
                'qty'               => 'required|integer|min:1',
                'price'             => 'nullable|numeric',
                'rate'              => 'nullable|numeric',
                'invoice_no'        => 'nullable|string|max:50',
            ]);

            $log = new PurchaseInwardLog();
            $log->purchase_order_id = $request->purchase_order_id;
            $log->material_id       = $request->material_id;
            $log->qty               = $request->qty;
            $log->price             = $request->price;
            $log->rate              = $request->rate;
            $log->invoice_no        = $request->invoice_no;
            $log->created_by        = auth()->id();
            $log->status            = $request->status ?? 0;
            $log->save();
            return response()->json(['message' => 'Purchase inward log created successfully',
                'data' => $log]);
        }catch(\Exception $e){
            return response()->json(['error' => 'Failed to store Purchase inward log', $e->getMessage()], 500);
        }
        
    }

    public function edit(Request $request, $id)
    {
        try{
            $log =PurchaseInwardLog::find($id);

            if(!$log){
                return response()->json(['error' => 'Purchase inward log not found'], 404);
            }
            return response()->json(['message' => 'Purchase inward log fetch  successfully',
                'data' => $log]);
        }catch(\Exception $e){
            return response()->json(['error' => 'Failed to fetch Purchase inward log', $e->getMessage()], 500);
        }
        
    }

    public function update(Request $request, $id)
    {
        try{
            $request->validate([
                'purchase_order_id' => 'required|exists:purchase_orders,id',
                'material_id'       => 'required|exists:materials,id',
                'qty'               => 'required|integer|min:1',
                'price'             => 'nullable|numeric',
                'rate'              => 'nullable|numeric',
                'invoice_no'        => 'nullable|string|max:50',
            ]);
            
            $log =PurchaseInwardLog::find($id);
            
            if(!$log){
                return response()->json(['error' => 'Purchase inward log not found'], 404);
            }
            $log->purchase_order_id = $request->purchase_order_id;
            $log->material_id       = $request->material_id;
            $log->qty               = $request->qty;
            $log->price             = $request->price;
            $log->rate              = $request->rate;
            $log->invoice_no        = $request->invoice_no;
            $log->status = $request->status ?? $log->status;
            $log->save();

            return response()->json(['message' => 'Purchase inward log updated  successfully',
                'data' => $log]);
        }catch(\Exception $e){
            return response()->json(['error' => 'Failed to fetch purchase inward log', $e->getMessage()], 500);
        }
        
    }

    public function delete(Request $request, $id){
        try{
            $log =PurchaseInwardLog::find($id);

            if(!$log){
                return response()->json(['error' => 'Purchase inward log not found'], 404);
            }

            $log->delete();
            return response()->json(['message' => 'Purchase inward log deleted  successfully']);
        }catch(\Exception $e){
            return response()->json(['error' => 'Failed to fetch purchase inward log', $e->getMessage()], 500);
        }
        
    }

    public function statusUpdate(Request $request)
    {
        try{
            $id = $request->id;
            $log =PurchaseInwardLog::find($id);

            if(!$log){
                return response()->json(['error' => 'Purchase inward log not found'], 404);
            }
            $log->status= !$log->status;
            $log->save();

            return response()->json(['message' => 'Purchase inward log status updated  successfully']);
        }catch(\Exception $e){
            return response()->json(['error' => 'Failed to fetch  purchase inward log', $e->getMessage()], 500);
        }
        
    }
}
