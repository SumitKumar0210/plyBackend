<?php

namespace App\Http\Controllers\Admin\modules;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\MachineOperator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class MachineOperatorController extends Controller
{
    public function getData(Request $request)
    {
        try{
            $operators = MachineOperator::with('purchaseOrder', 'machine', 'user:id,name')->orderBy('id','desc')->paginate(10);
            return response()->json($operators);
        }catch(\Exception $e){
            return response()->json(['error' => 'Failed to fetch machine operators'], 500);
        }
        
    }

    public function search(Request $request)
    {
        try {
            $query = MachineOperator::with('purchaseOrder', 'machine', 'user:id,name')->orderBy('id', 'desc');

            if ($request->filled('machine')) {
                $query->whereHas('machine', function ($q) use ($request) {
                    $q->where('name', 'ILIKE', '%' . $request->machine . '%'); 
                });
            }

            if ($request->filled('purchase_no')) {
                $query->whereHas('purchaseOrder', function ($q) use ($request) {
                    $q->where('purchase_no', 'ILIKE', '%' . $request->purchase_no . '%'); 
                });
            }
            if ($request->filled('user')) {
                $query->whereHas('user', function ($q) use ($request) {
                    $q->where('name', 'ILIKE', '%' . $request->user . '%'); 
                });
            }

            if ($request->filled('runing_hours')) {
                $query->where('runing_hours', 'ILIKE', '%' . $request->runing_hours . '%');
            }

            if ($request->has('status') && $request->status !== null) {
                $query->where('status', $request->status);
            }

            $handTools = $query->paginate(10);
            return response()->json($handTools);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to fetch hand tools',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function store(Request $request)
    {
        try{
            
            $request->validate([
                'po_id'         => 'required|integer|exists:production_orders,id',
                'machine_id'    => 'required|integer|exists:machines,id',
                // 'operator_id'   => 'required|integer|exists:users,id',
                'runing_hours'  => 'required|numeric|min:0',
                'remark'        => 'nullable|string|max:500',
            ]);


            $operator = new MachineOperator();

            $operator->po_id = $request->po_id;
            $operator->machine_id = $request->machine_id;
            $operator->user_id = $request->user_id;
            $operator->runing_hours = $request->runing_hours;
            $operator->remark = $request->remark;
            $operator->status = $request->status ?? 0;
            $operator->save();
            return response()->json(['message' => 'Machine operator created successfully',
                'data' => $operator]);
        }catch(\Exception $e){
            return response()->json(['error' => 'Failed to store machine operator', $e->getMessage()], 500);
        }
        
    }

    public function edit(Request $request, $id)
    {
        try{
            $operator =MachineOperator::find($id);

            if(!$operator){
                return response()->json(['error' => 'Machine operator not found'], 404);
            }
            return response()->json(['message' => 'Machine operator fetch  successfully',
                'data' => $operator]);
        }catch(\Exception $e){
            return response()->json(['error' => 'Failed to fetch machine operator', $e->getMessage()], 500);
        }
        
    }

    public function update(Request $request, $id)
    {
        try{
            $request->validate([
                'po_id'         => 'required|integer|exists:production_orders,id',
                'machine_id'    => 'required|integer|exists:machines,id',
                // 'operator_id'   => 'required|integer|exists:users,id',
                'runing_hours'  => 'required|numeric|min:0',
                'remark'        => 'nullable|string|max:500',
            ]);
            $operator =MachineOperator::find($id);

            if(!$operator){
                return response()->json(['error' => 'Machine operator not found'], 404);
            }
            $operator->po_id = $request->po_id;
            $operator->machine_id = $request->machine_id;
            $operator->user_id = $request->user_id;
            $operator->runing_hours = $request->runing_hours;
            $operator->remark = $request->remark;
            $operator->status = $request->status ?? $operator->status;
            $operator->save();

            return response()->json(['message' => 'Machine operator updated  successfully',
                'data' => $operator]);
        }catch(\Exception $e){
            return response()->json(['error' => 'Failed to fetch machine operator', $e->getMessage()], 500);
        }
        
    }

    public function delete(Request $request, $id){
        try{
            $operator =MachineOperator::find($id);

            if(!$operator){
                return response()->json(['error' => 'Machine operator not found'], 404);
            }

            $operator->delete();
            return response()->json(['message' => 'Machine operator deleted  successfully']);
        }catch(\Exception $e){
            return response()->json(['error' => 'Failed to fetch machine operator', $e->getMessage()], 500);
        }
        
    }

    public function statusUpdate(Request $request)
    {
        try{
            $id = $request->id;
            $operator =MachineOperator::find($id);

            if(!$operator){
                return response()->json(['error' => 'Machine operator not found'], 404);
            }
            $operator->status= !$operator->status;
            $operator->save();

            return response()->json(['message' => 'Machine operator status updated  successfully']);
        }catch(\Exception $e){
            return response()->json(['error' => 'Failed to fetch  machine operator', $e->getMessage()], 500);
        }
        
    }
}
