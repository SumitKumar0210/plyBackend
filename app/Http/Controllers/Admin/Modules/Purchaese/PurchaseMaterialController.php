<?php

namespace App\Http\Controllers\Admin\Modules\Purchaese;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\PurchaseMaterial;
use Illuminate\Validation\Rule;

class PurchaseMaterialController extends Controller
{
    // public function __construct()
    // {
    //     $this->middleware('auth:api');

    //     $this->middleware('permission:purchase_order.read')->only([
    //         'getData', 'search'
    //     ]);

    //     $this->middleware('permission:purchase_order.create')->only([
    //         'store'
    //     ]);

    //     $this->middleware('permission:purchase_order.update')->only([
    //         'edit', 'update', 'statusUpdate'
    //     ]);

    //     $this->middleware('permission:purchase_order.delete')->only([
    //         'delete'
    //     ]);
        
    // }
    
    public function getData(Request $request)
    {
        try{
            $materials  = PurchaseMaterial::with('purchaseOrder','material')->orderBy('id','desc')->paginate(10);
            return response()->json($materials);
        }catch(\Exception $e){
            return response()->json(['error' => 'Failed to fetch purchase materials'], 500);
        }
        
    }

    public function search(Request $request)
    {
        try {
            $query = PurchaseMaterial::with('purchaseOrder','material')->orderBy('id', 'desc');

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

            if ($request->filled('size')) {
                $query->where('size', 'ILIKE', '%' . $request->size . '%');
            }

            if ($request->filled('rate')) {
                $query->where('rate', 'ILIKE', '%' . $request->rate . '%');
            }

            if ($request->filled('actual_qty')) {
                $query->where('actual_qty', 'ILIKE', '%' . $request->actual_qty . '%');
            }

            // Search by Status
            if ($request->has('status') && $request->status !== null) {
                $query->where('status', $request->status);
            }

            $materials = $query->paginate(10);

            return response()->json($materials);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to fetch materials',
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
                'rate'              => 'nullable|numeric',
                'size'              => 'nullable|string|max:225',
                'actual_qty'        => 'nullable|integer',
                'status'            => 'nullable|in:0,1',
            ]);

            $material = new PurchaseMaterial();
            $material->purchase_order_id = $request->purchase_order_id;
            $material->material_id       = $request->material_id;
            $material->qty               = $request->qty;
            $material->rate              = $request->rate;
            $material->size              = $request->size;
            $material->actual_qty        = $request->actual_qty;
            $material->status            = $request->status ?? 0;
            $material->save();
            return response()->json(['message' => 'Purchase material created successfully',
                'data' => $material]);
        }catch(\Exception $e){
            return response()->json(['error' => 'Failed to store purchase material', $e->getMessage()], 500);
        }
        
    }

    public function edit(Request $request, $id)
    {
        try{
            $material =PurchaseMaterial::find($id);

            if(!$material){
                return response()->json(['error' => 'Purchase material not found'], 404);
            }
            return response()->json(['message' => 'Purchase material fetch  successfully',
                'data' => $material]);
        }catch(\Exception $e){
            return response()->json(['error' => 'Failed to fetch purchase material', $e->getMessage()], 500);
        }
        
    }

    public function update(Request $request, $id)
    {
        try{
            $request->validate([
                'purchase_order_id' => 'required|exists:purchase_orders,id',
                'material_id'       => 'required|exists:materials,id',
                'qty'               => 'required|integer|min:1',
                'rate'              => 'nullable|numeric',
                'size'              => 'nullable|string|max:225',
                'actual_qty'        => 'nullable|integer',
                'status'            => 'nullable|in:0,1',
            ]);
            
            $material =PurchaseMaterial::find($id);
            
            if(!$material){
                return response()->json(['error' => 'Purchase material not found'], 404);
            }

            $material->purchase_order_id = $request->purchase_order_id;
            $material->material_id       = $request->material_id;
            $material->qty               = $request->qty;
            $material->rate              = $request->rate;
            $material->size              = $request->size;
            $material->actual_qty        = $request->actual_qty;
            $material->status            = $request->status ?? $material->status;
            $material->save();

            return response()->json(['message' => 'Purchase material updated  successfully',
                'data' => $material]);
        }catch(\Exception $e){
            return response()->json(['error' => 'Failed to fetch purchase material', $e->getMessage()], 500);
        }
        
    }

    public function delete(Request $request, $id){
        try{
            $material =PurchaseMaterial::find($id);

            if(!$material){
                return response()->json(['error' => 'Purchase material not found'], 404);
            }

            $material->delete();
            return response()->json(['message' => 'Purchase material deleted  successfully']);
        }catch(\Exception $e){
            return response()->json(['error' => 'Failed to fetch purchase material', $e->getMessage()], 500);
        }
        
    }

    public function statusUpdate(Request $request)
    {
        try{
            $id = $request->id;
            $material =PurchaseMaterial::find($id);

            if(!$material){
                return response()->json(['error' => 'Purchase material not found'], 404);
            }
            $material->status= !$material->status;
            $material->save();

            return response()->json(['message' => 'Purchase material status updated  successfully']);
        }catch(\Exception $e){
            return response()->json(['error' => 'Failed to fetch  purchase material', $e->getMessage()], 500);
        }
        
    }
}
