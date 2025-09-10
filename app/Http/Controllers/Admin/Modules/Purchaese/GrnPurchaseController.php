<?php

namespace App\Http\Controllers\Admin\modules\Purchaese;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\GrnPurchase;
use Illuminate\Validation\Rule;

class GrnPurchaseController extends Controller
{
    public function getData(Request $request)
    {
        try{
            $purchases = GrnPurchase::with('purchaseOrder')->orderBy('id','desc')->paginate(10);
            return response()->json($purchases);
        }catch(\Exception $e){
            return response()->json(['error' => 'Failed to fetch grn purchases data'], 500);
        }
        
    }

    public function search(Request $request)
    {
        try{
            $query = GrnPurchase::with('purchaseOrder')->orderBy('id','desc');

            if($request->filled('purchase_no')){
                $query->whereHas('purchaseOrder', function ($q) use ($request){
                    $q->where('purchase_no', 'ILIKE', '%'. $request->purchase_no .'%');
                });
            }

            if($request->filled('note')){
                $query->where('note', 'ILIKE', '%'. $request->note .'%');
            }
            
            if ($request->has('status') && $request->status !== null) {
                $query->where('status', $request->status);
            }
            $purchases = $query->paginate(10);
            return response()->json($purchases);
        }catch(\Exception $e){
            return response()->json(['error' => 'Failed to fetch grn purchases data'], 500);
        }
        
    }

    public function store(Request $request)
    {
        try{
            
            $request->validate([
                'purchase_order_id' => [
                    'required',
                    Rule::unique('grn_purchases', 'purchase_order_id')->whereNull('deleted_at'),
                ],
            ]);

            $purchase = new GrnPurchase();

            $purchase->purchase_order_id = $request->purchase_order_id;
            $purchase->note = $request->note;
            $purchase->status = $request->status ?? 0;
            $purchase->save();
            return response()->json(['message' => 'Grn purchase created successfully',
                'data' => $purchase]);
        }catch(\Exception $e){
            return response()->json(['error' => 'Failed to store grn purchase', $e->getMessage()], 500);
        }
        
    }

    public function edit(Request $request, $id)
    {
        try{
            $purchase =GrnPurchase::find($id);

            if(!$purchase){
                return response()->json(['error' => 'Grn purchase not found'], 404);
            }
            return response()->json(['message' => 'Grn purchase fetch  successfully',
                'data' => $purchase]);
        }catch(\Exception $e){
            return response()->json(['error' => 'Failed to fetch grn purchase', $e->getMessage()], 500);
        }
        
    }

    public function update(Request $request, $id)
    {
        try{
            $request->validate([
                'purchase_order_id' => [
                    'required',
                    Rule::unique('grn_purchases', 'purchase_order_id')
                        ->ignore($id) 
                        ->whereNull('deleted_at'), 
                ],
                'status' => 'nullable|in:0,1',
            ]);
            $purchase =GrnPurchase::find($id);

            if(!$purchase){
                return response()->json(['error' => 'Grn purchase not found'], 404);
            }
            $purchase->purchase_order_id = $request->purchase_order_id;
            $purchase->note = $request->note;
            $purchase->status = $request->status ?? $purchase->status;
            $purchase->save();

            return response()->json(['message' => 'Grn purchase updated  successfully',
                'data' => $purchase]);
        }catch(\Exception $e){
            return response()->json(['error' => 'Failed to fetch grn purchase', $e->getMessage()], 500);
        }
        
    }

    public function delete(Request $request, $id){
        try{
            $purchase =GrnPurchase::find($id);

            if(!$purchase){
                return response()->json(['error' => 'Grn purchase not found'], 404);
            }

            $purchase->delete();
            return response()->json(['message' => 'Grn purchase deleted  successfully']);
        }catch(\Exception $e){
            return response()->json(['error' => 'Failed to fetch grn purchase', $e->getMessage()], 500);
        }
        
    }

    public function statusUpdate(Request $request)
    {
        try{
            $id = $request->id;
            $purchase =GrnPurchase::find($id);

            if(!$purchase){
                return response()->json(['error' => 'Grn purchase not found'], 404);
            }
            $purchase->status= !$purchase->status;
            $purchase->save();

            return response()->json(['message' => 'Grn purchase status updated  successfully']);
        }catch(\Exception $e){
            return response()->json(['error' => 'Failed to fetch  grn purchase', $e->getMessage()], 500);
        }
        
    }
}
