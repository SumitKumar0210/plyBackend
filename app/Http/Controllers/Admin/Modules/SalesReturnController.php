<?php

namespace App\Http\Controllers\Admin\modules;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\SalesReturn;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class SalesReturnController extends Controller
{
    public function getData(Request $request)
    {
        try{
            $saleReturns = SalesReturn::with('product', 'purchaseOrder')->orderBy('id','desc')->paginate(10);
            return response()->json($saleReturns);
        }catch(\Exception $e){
            return response()->json(['error' => 'Failed to fetch Sales return'], 500);
        }
        
    }

    public function search(Request $request)
    {
        try {
            $query = SalesReturn::with('product', 'purchaseOrder')->orderBy('id', 'desc');

            if ($request->filled('product')) {
                $query->whereHas('product', function ($q) use ($request) {
                    $q->where('name', 'ILIKE', '%' . $request->product . '%'); 
                });
            }

            if ($request->filled('purchase_no')) {
                $query->whereHas('purchaseOrder', function ($q) use ($request) {
                    $q->where('purchase_no', 'ILIKE', '%' . $request->purchase_no . '%'); 
                });
            }

            if ($request->filled('reason')) {
                $query->where('reason', 'ILIKE', '%' . $request->reason . '%');
            }

            if ($request->filled('qty')) {
                $query->where('qty', 'ILIKE', '%' . $request->qty . '%');
            }

            if ($request->has('status') && $request->status !== null) {
                $query->where('status', $request->status);
            }

            $saleReturns = $query->paginate(10);
            return response()->json($saleReturns);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to fetch Sales return',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function store(Request $request)
    {
        try{
            
            $request->validate([
                'po_id'       => 'required|integer|exists:production_orders,id',
                'product_id'  => 'required|integer|exists:products,id',
                'qty'         => 'required|numeric|min:1',
                'reason'      => 'nullable|string|max:500',
            ]);


            $saleReturn = new SalesReturn();

            $saleReturn->po_id = $request->po_id;
            $saleReturn->product_id = $request->product_id;
            $saleReturn->qty = $request->qty;
            $saleReturn->reason = $request->reason;
            $saleReturn->status = $request->status ?? 0;
            if($request->hasFile('doc')) {
                $image = $request->file('doc');
               $randomName = rand(10000000, 99999999);
                $imageName = time().'_'.$randomName . '.' . $image->getClientOriginalExtension();
                $image->move(public_path('uploads/SalesReport'), $imageName);
                $saleReturn->doc = '/uploads/SalesReport/'.$imageName;
            }   
            $saleReturn->save();
            return response()->json(['message' => 'Sales return created successfully',
                'data' => $saleReturn]);
        }catch(\Exception $e){
            return response()->json(['error' => 'Failed to store sales return', $e->getMessage()], 500);
        }
        
    }

    public function edit(Request $request, $id)
    {
        try{
            $saleReturn =SalesReturn::find($id);

            if(!$saleReturn){
                return response()->json(['error' => 'Sales return not found'], 404);
            }
            return response()->json(['message' => 'Sales return fetch  successfully',
                'data' => $saleReturn]);
        }catch(\Exception $e){
            return response()->json(['error' => 'Failed to fetch sales return', $e->getMessage()], 500);
        }
        
    }

    public function update(Request $request, $id)
    {
        try{
            $request->validate([
                'po_id'       => 'required|integer|exists:production_orders,id',
                'product_id'  => 'required|integer|exists:products,id',
                'qty'         => 'required|numeric|min:1',
                'reason'      => 'nullable|string|max:500',
            ]);

            $saleReturn =SalesReturn::find($id);

            if(!$saleReturn){
                return response()->json(['error' => 'Sales return not found'], 404);
            }
            $saleReturn->po_id = $request->po_id;
            $saleReturn->product_id = $request->product_id;
            $saleReturn->qty = $request->qty;
            $saleReturn->reason = $request->reason;
            if($request->hasFile('doc')) {
                $image = $request->file('doc');
               $randomName = rand(10000000, 99999999);
                $imageName = time().'_'.$randomName . '.' . $image->getClientOriginalExtension();
                $image->move(public_path('uploads/SalesReport'), $imageName);
                $saleReturn->doc = '/uploads/SalesReport/'.$imageName;
            }   
            $saleReturn->status = $request->status ?? $saleReturn->status;
            $saleReturn->save();

            return response()->json(['message' => 'Sales return updated  successfully',
                'data' => $saleReturn]);
        }catch(\Exception $e){
            return response()->json(['error' => 'Failed to fetch sales return', $e->getMessage()], 500);
        }
        
    }

    public function delete(Request $request, $id){
        try{
            $saleReturn =SalesReturn::find($id);

            if(!$saleReturn){
                return response()->json(['error' => 'Sales return not found'], 404);
            }

            $saleReturn->delete();
            return response()->json(['message' => 'Sales return deleted  successfully']);
        }catch(\Exception $e){
            return response()->json(['error' => 'Failed to fetch sales return', $e->getMessage()], 500);
        }
        
    }

    public function statusUpdate(Request $request)
    {
        try{
            $id = $request->id;
            $saleReturn =SalesReturn::find($id);

            if(!$saleReturn){
                return response()->json(['error' => 'Sales return not found'], 404);
            }
            $saleReturn->status= !$saleReturn->status;
            $saleReturn->save();

            return response()->json(['message' => 'Sales return status updated  successfully']);
        }catch(\Exception $e){
            return response()->json(['error' => 'Failed to fetch  sales return', $e->getMessage()], 500);
        }
        
    }
}
