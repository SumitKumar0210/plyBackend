<?php

namespace App\Http\Controllers\Admin\Modules;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\TentativeItem;
use Illuminate\Validation\Rule;

class TentativeItemController extends Controller
{
    public function getData(Request $request)
    {
        try{
            $itmes = TentativeItem::with('purchaseOrder')->orderBy('id','desc')->paginate(10);
            return response()->json($itmes);
        }catch(\Exception $e){
            return response()->json(['error' => 'Failed to fetch tentative item'], 500);
        }
        
    }

    public function search(Request $request)
    {
        try{
            $query = TentativeItem::with('purchaseOrder')->orderBy('id','desc');

            if($request->filled('purchase_no')){
                $query->whereHas('purchaseOrder', function($q) use ($request){
                    $q->where('purchase_no','ILIKE', '%'. $request->purchase_no . '%');
                });
            }

            if ($request->filled('items')) {
                $query->whereRaw("product_items::jsonb @> ?", [json_encode([['name' => $request->items]])]);
            }

            if ($request->has('status') && $request->status !== null) {
                $query->where('status', $request->status);
            }

            $itmes = $query->paginate(10);
            return response()->json($itmes);
        }catch(\Exception $e){
            return response()->json(['error' => 'Failed to fetch tentative item'], 500);
        }
    }

    public function store(Request $request)
    {
        try{
            
            $request->validate([
                'po_id' => 'required',
                'product_items' => 'required'
            ]);

            $itme = new TentativeItem();

            $itme->po_id = $request->po_id;
            $itme->product_items = $request->product_items ?? null;
            $itme->status = $request->status ?? 0;
            $itme->save();
            return response()->json(['message' => 'Tentative item created successfully',
                'data' => $itme]);
        }catch(\Exception $e){
            return response()->json(['error' => 'Failed to store tentative item', $e->getMessage()], 500);
        }
        
    }

    public function edit(Request $request, $id)
    {
        try{
            $itme = TentativeItem::find($id);

            if(!$itme){
                return response()->json(['error' => 'Tentative item not found'], 404);
            }
            return response()->json(['message' => 'Tentative item fetch  successfully',
                'data' => $itme]);
        }catch(\Exception $e){
            return response()->json(['error' => 'Failed to fetch tentative item', $e->getMessage()], 500);
        }
        
    }

    public function update(Request $request, $id)
    {
        try{
            $request->validate([
                'po_id' => 'required',
                'status' => 'nullable|in:0,1',
                'product_items' => 'required'
            ]);
            $itme = TentativeItem::find($id);

            if(!$itme){
                return response()->json(['error' => 'Tentative item not found'], 404);
            }
            $itme->po_id = $request->po_id;
            $itme->product_items = $request->product_items ? json_encode($request->product_items) : null;
            $itme->status = $request->status ?? $itme->status;
            $itme->save();

            return response()->json(['message' => 'Tentative item updated  successfully',
                'data' => $itme]);
        }catch(\Exception $e){
            return response()->json(['error' => 'Failed to fetch tentative item', $e->getMessage()], 500);
        }
        
    }

    public function delete(Request $request, $id){
        try{
            $itme = TentativeItem::find($id);

            if(!$itme){
                return response()->json(['error' => 'Tentative item not found'], 404);
            }

            $itme->delete();
            return response()->json(['message' => 'Tentative item deleted  successfully']);
        }catch(\Exception $e){
            return response()->json(['error' => 'Failed to fetch tentative item', $e->getMessage()], 500);
        }
        
    }

    public function statusUpdate(Request $request)
    {
        try{
            $id = $request->id;
            $itme = TentativeItem::find($id);

            if(!$itme){
                return response()->json(['error' => 'Tentative item not found'], 404);
            }
            $itme->status= !$itme->status;
            $itme->save();

            return response()->json(['message' => 'Tentative item status updated  successfully']);
        }catch(\Exception $e){
            return response()->json(['error' => 'Failed to fetch  tentative item', $e->getMessage()], 500);
        }
        
    }
}
