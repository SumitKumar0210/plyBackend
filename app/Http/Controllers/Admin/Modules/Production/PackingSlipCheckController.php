<?php

namespace App\Http\Controllers\Admin\Modules\Production;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\PackingSlip;
use Illuminate\Validation\Rule;

class PackingSlipController extends Controller
{
    public function getData(Request $request)
    {
        try{
            $packing_slips = PackingSlip::orderBy('id','desc')->paginate(10);
            return response()->json($packing_slips);
        }catch(\Exception $e){
            return response()->json(['error' => 'Failed to fetch packing slip data'], 500);
        }
        
    }

    public function store(Request $request)
    {
        try{
            
            $request->validate([
                'po_id' => ['required', Rule::unique('packing_slips', 'po_id')->whereNull('deleted_at'),],
            ]);
            $imgArray = [];
            $packing_slip = new PackingSlip();

            $packing_slip->po_id = $request->po_id;
            $packing_slip->product_id = $request->product_id;
            $packing_slip->store = $request->store;
            $packing_slip->material_type = $request->material_type;
            $packing_slip->no_of_cartons = $request->no_of_cartons;
            $packing_slip->description = $request->description;
            $packing_slip->status = $request->status ?? 0;
            $packing_slip->save();
            return response()->json(['message' => 'packing slip  created successfully',
                'data' => $packing_slip]);
        }catch(\Exception $e){
            return response()->json(['error' => 'Failed to store packing slip data', $e->getMessage()], 500);
        }
        
    }

    public function edit(Request $request, $id)
    {
        try{
            $packing_slip = PackingSlip::find($id);

            if(!$packing_slip){
                return response()->json(['error' => 'Packing slip data not found'], 404);
            }
            return response()->json(['message' => 'Packing slip data fetch  successfully',
                'data' => $packing_slip]);
        }catch(\Exception $e){
            return response()->json(['error' => 'Failed to fetch packing slip data', $e->getMessage()], 500);
        }
        
    }

    public function update(Request $request, $id)
    {
        try{
            $request->validate([
                'po_id' => ['required',
                        Rule::unique('packing_slips', 'po_id')
                        ->ignore($id) 
                        ->whereNull('deleted_at'),]
            ]);
            $packing_slip = PackingSlip::find($id);

            if(!$packing_slip){
                return response()->json(['error' => 'Packing slip data not found'], 404);
            }

            $packing_slip->po_id = $request->po_id;
            $packing_slip->product_id = $request->product_id;
            $packing_slip->store = $request->store;
            $packing_slip->material_type = $request->material_type;
            $packing_slip->no_of_cartons = $request->no_of_cartons;
            $packing_slip->description = $request->description;
            $packing_slip->status = $request->status ?? $packing_slip->status;
            $packing_slip->save();

            return response()->json(['message' => 'Packing slip  updated  successfully',
                'data' => $packing_slip]);
        }catch(\Exception $e){
            return response()->json(['error' => 'Failed to fetch packing slip data', $e->getMessage()], 500);
        }
        
    }

    public function delete(Request $request, $id){
        try{
            $packing_slip = PackingSlip::find($id);

            if(!$packing_slip){
                return response()->json(['error' => 'Packing slip data not found'], 404);
            }

            $packing_slip->delete();
            return response()->json(['message' => 'Packing slip  deleted  successfully']);
        }catch(\Exception $e){
            return response()->json(['error' => 'Failed to fetch packing slip data', $e->getMessage()], 500);
        }
        
    }

    public function statusUpdate(Request $request)
    {
        try{
            $id = $request->id;
            $packing_slip = PackingSlip::find($id);

            if(!$packing_slip){
                return response()->json(['error' => 'Packing slip data  not found'], 404);
            }
            $packing_slip->status= !$packing_slip->status;
            $packing_slip->save();

            return response()->json(['message' => 'Packing slip  status updated  successfully']);
        }catch(\Exception $e){
            return response()->json(['error' => 'Failed to fetch  packing slip data', $e->getMessage()], 500);
        }
        
    }
}
