<?php

namespace App\Http\Controllers\Admin\Modules\Master;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\TaxSlab;
use Illuminate\Validation\Rule;

class TaxSlabController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api');

        $this->middleware('permission:tax_slabs.read')->only([
            'getData', 'search'
        ]);

        $this->middleware('permission:tax_slabs.create')->only([
            'store'
        ]);

        $this->middleware('permission:tax_slabs.update')->only([
            'edit', 'update', 'statusUpdate'
        ]);

        $this->middleware('permission:tax_slabs.delete')->only([
            'delete'
        ]);
    }
    
    public function getData (Request $request)
    {
        
        try {

            $query = TaxSlab::orderBy('id', 'desc');
        
            if ($request->has('search') && !empty($request->search)) {
                $query->where('percentage', 'ILIKE', '%' . $request->search . '%');
            }
            
            if ($request->has('status')) {
                $query->where('status', $request->status);
            }
        
            if ($request->has('status')) {
                $taxSlabs = $query->get();
                return response()->json(['data' => $taxSlabs]);
            }
        
            $perPage = $request->per_page ?? 10;
        
            $taxSlabs = $query->paginate($perPage);
        
            return response()->json($taxSlabs);
        
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to fetch tax slab'], 500);
        }

        
    }

    public function search(Request $request)
    {
        try{
            $query = TaxSlab::orderBy('id','desc');
            if ($request->has('name') && !empty($request->name)) {
                $query->where('name', 'like', '%' . $request->name . '%');
            }
            if ($request->has('status') && $request->status !== null) {
                $query->where('status', $request->status);
            }
            $taxSlabs = $query->paginate(10);
            return response()->json($taxSlabs);
        }catch(\Exception $e){
            return response()->json(['error' => 'Failed to fetch tax slab'], 500);
        }
        
    }

    public function store(Request $request)
    {
        try{
            
            $request->validate([
                'percentage' => [
                    'required',
                    'string',
                    'max:255',
                    Rule::unique('tax_slabs', 'percentage')->whereNull('deleted_at'),
                ],
            ]);


            $taxSlab = new TaxSlab();

            $taxSlab->percentage = $request->percentage;
            $taxSlab->save();
            return response()->json(['message' => 'Tax slab created successfully',
                'data' => $taxSlab]);
        }catch(\Exception $e){
            return response()->json(['error' => 'Failed to fetch tax slab', $e->getMessage()], 500);
        }
        
    }

    public function edit(Request $request, $id)
    {
        try{
            $taxSlab =TaxSlab::find($id);

            if(!$taxSlab){
                return response()->json(['error' => 'Tax slab not found'], 404);
            }
            return response()->json(['message' => 'Tax slab fetch  successfully',
                'data' => $taxSlab]);
        }catch(\Exception $e){
            return response()->json(['error' => 'Failed to fetch tax slab', $e->getMessage()], 500);
        }
        
    }

    public function update(Request $request, $id)
    {
        try{
            $request->validate([
                'percentage' => [
                    'required',
                    'string',
                    'max:255',
                    Rule::unique('tax_slabs', 'percentage')
                        ->ignore($id) 
                        ->whereNull('deleted_at'), 
                ],
                'status' => 'nullable|in:0,1',
            ]);

            $taxSlab =TaxSlab::find($id);

            if(!$taxSlab){
                return response()->json(['error' => 'Tax slab not found'], 404);
            }
            $taxSlab->percentage = $request->percentage;
            $taxSlab->status = $request->status ?? $taxSlab->status;
            $taxSlab->save();

            return response()->json(['message' => 'Tax slab updated  successfully',
                'data' => $taxSlab]);
        }catch(\Exception $e){
            return response()->json(['error' => 'Failed to fetch tax slab', $e->getMessage()], 500);
        }
        
    }

    public function delete(Request $request, $id)
    {
        try {
            $taxSlab = TaxSlab::find($id);
    
            if (!$taxSlab) {
                return response()->json([
                    'error' => 'Tax slab not found'
                ], 404);
            }
    
            $taxSlab->delete();
    
            return response()->json([
                'message' => 'Tax slab deleted successfully'
            ], 200);
    
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to delete Tax slab',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function statusUpdate(Request $request)
    {
        try{
            $id = $request->id;
            $taxSlab =TaxSlab::find($id);

            if(!$taxSlab){
                return response()->json(['error' => 'Tax slab not found'], 404);
            }
            $taxSlab->status= $request->status ?? $taxSlab->status;
            $taxSlab->save();

            return response()->json(['message' => 'Tax slab status updated  successfully']);
        }catch(\Exception $e){
            return response()->json(['error' => 'Failed to fetch tax slab', $e->getMessage()], 500);
        }
        
    }
}
