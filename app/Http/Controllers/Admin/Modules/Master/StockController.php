<?php

namespace App\Http\Controllers\Admin\Modules\Master;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Stock;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class StockController extends Controller
{
    public function getData(Request $request)
    {
        try{
            $stocks = Stock::with('material')->orderBy('id','desc')->paginate(10);
            return response()->json($stocks);
        }catch(\Exception $e){
            return response()->json(['error' => 'Failed to fetch Stocks'], 500);
        }
        
    }

    public function search(Request $request)
    {
        try {
            $query = Stock::with('material')->orderBy('id', 'desc');

            if ($request->filled('material')) {
                $query->whereHas('material', function ($q) use ($request) {
                    $q->where('name', 'ILIKE', '%' . $request->material . '%'); 
                });
            }

            if ($request->filled('in_stock')) {
                $query->where('in_stock', 'ILIKE', '%' . $request->in_stock . '%');
            }

            if ($request->filled('out_stock')) {
                $query->where('out_stock', 'ILIKE', '%' . $request->out_stock . '%');
            }

            if ($request->has('status') && $request->status !== null) {
                $query->where('status', $request->status);
            }

            $stocks = $query->paginate(10);
            return response()->json($stocks);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to fetch Stocks',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function store(Request $request)
    {
        try{
            $request->validate([
                'material_id' => [
                    'required',
                    Rule::unique('stocks', 'material_id')->whereNull('deleted_at'),
                ],
            ]);

            $stock = new Stock();

            $stock->material_id = $request->material_id;
            $stock->in_stock = $request->in_stock;
            $stock->out_stock = $request->out_stock;
            $stock->status = $request->status ?? 0;
            $stock->save();
            return response()->json(['message' => 'Stock created successfully',
                'data' => $stock]);
        }catch(\Exception $e){
            return response()->json(['error' => 'Failed to store stock', $e->getMessage()], 500);
        }
        
    }

    public function edit(Request $request, $id)
    {
        try{
            $stock =Stock::find($id);

            if(!$stock){
                return response()->json(['error' => 'Stock not found'], 404);
            }
            return response()->json(['message' => 'Stock fetch  successfully',
                'data' => $stock]);
        }catch(\Exception $e){
            return response()->json(['error' => 'Failed to fetch stock', $e->getMessage()], 500);
        }
        
    }

    public function update(Request $request, $id)
    {
        try{
            $request->validate([
                'material_id' => [
                    'required',
                    Rule::unique('stocks', 'material_id')
                        ->ignore($id) 
                        ->whereNull('deleted_at'), 
                ],
                'status' => 'nullable|in:0,1',
            ]);
            $stock =Stock::find($id);

            if(!$stock){
                return response()->json(['error' => 'Stock not found'], 404);
            }
           $stock->material_id = $request->material_id;
            $stock->in_stock = $request->in_stock;
            $stock->out_stock = $request->out_stock;
            $stock->status = $request->status ?? $stock->status;
            $stock->save();

            return response()->json(['message' => 'Stock updated  successfully',
                'data' => $stock]);
        }catch(\Exception $e){
            return response()->json(['error' => 'Failed to fetch stock', $e->getMessage()], 500);
        }
        
    }

    public function delete(Request $request, $id){
        try{
            $stock =Stock::find($id);

            if(!$stock){
                return response()->json(['error' => 'Stock not found'], 404);
            }

            $stock->delete();
            return response()->json(['message' => 'Stock deleted  successfully']);
        }catch(\Exception $e){
            return response()->json(['error' => 'Failed to fetch stock', $e->getMessage()], 500);
        }
        
    }

    public function statusUpdate(Request $request)
    {
        try{
            $id = $request->id;
            $stock =Stock::find($id);

            if(!$stock){
                return response()->json(['error' => 'Stock not found'], 404);
            }
            $stock->status= !$stock->status;
            $stock->save();

            return response()->json(['message' => 'Stock status updated  successfully']);
        }catch(\Exception $e){
            return response()->json(['error' => 'Failed to fetch  stock', $e->getMessage()], 500);
        }
        
    }
}
