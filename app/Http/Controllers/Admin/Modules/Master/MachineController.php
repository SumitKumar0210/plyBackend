<?php

namespace App\Http\Controllers\Admin\Modules\Master;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Machine;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class MachineController extends Controller
{
    
    public function __construct()
    {
        $this->middleware('auth:api');

        $this->middleware('permission:machines.read')->only([
            'getData'
        ]);

        $this->middleware('permission:machines.create')->only([
            'store'
        ]);

        $this->middleware('permission:machines.update')->only([
            'edit', 'update', 'statusUpdate'
        ]);

        $this->middleware('permission:machines.delete')->only([
            'delete'
        ]);
    }
    
    public function getData(Request $request)
    {
        try{
            $machines = Machine::orderBy('id','desc')->paginate(10);
            return response()->json($machines);
        }catch(\Exception $e){
            return response()->json(['error' => 'Failed to fetch machine'], 500);
        }
        
    }

    public function store(Request $request)
    {
        try{
            
            $request->validate([
                'name' => [
                    'required',
                    'string',
                    'max:255',
                    Rule::unique('machines', 'name')->whereNull('deleted_at'),
                ],
            ]);

            $machine = new Machine();

            $machine->name = $request->name;
            $machine->run_hours_at_service = $request->run_hours_at_service;
            $machine->last_maintenance_date = date('Y-m-d', strtotime($request->last_maintenance_date));
            $machine->remarks = $request->remarks;
            $machine->cycle_days = $request->cycle_days;
            $machine->cycle_month = $request->cycle_month;
            $machine->message = $request->message;
            $machine->status = $request->status ?? 1;
            $machine->save();
            return response()->json(['message' => 'Machine created successfully',
                'data' => $machine]);
        }catch(\Exception $e){
            return response()->json(['error' => 'Failed to store machine', $e->getMessage()], 500);
        }
        
    }

    public function edit(Request $request, $id)
    {
        try{
            $machine =Machine::find($id);

            if(!$machine){
                return response()->json(['error' => 'Machine not found'], 404);
            }
            return response()->json(['message' => 'Machine fetch  successfully',
                'data' => $machine]);
        }catch(\Exception $e){
            return response()->json(['error' => 'Failed to fetch machine', $e->getMessage()], 500);
        }
        
    }

    public function update(Request $request, $id)
    {
        try{
            $request->validate([
                'name' => [
                    'required',
                    'string',
                    'max:255',
                    Rule::unique('machines', 'name')
                        ->ignore($id) 
                        ->whereNull('deleted_at'), 
                ],
                'status' => 'nullable|in:0,1',
            ]);
            $machine =Machine::find($id);
            
            if(!$machine){
                return response()->json(['error' => 'Machine not found'], 404);
            }
            $machine->name = $request->name;
            $machine->run_hours_at_service = $request->run_hours_at_service;
            $machine->last_maintenance_date = date('Y-m-d', strtotime($request->last_maintenance_date));
            $machine->remarks = $request->remarks;
            $machine->cycle_days = $request->cycle_days;
            $machine->cycle_month = $request->cycle_month;
            $machine->message = $request->message;
            $machine->status = $request->status ?? $machine->status;
            $machine->save();

            return response()->json(['message' => 'Machine updated  successfully',
                'data' => $machine]);
        }catch(\Exception $e){
            return response()->json(['error' => 'Failed to fetch machine', $e->getMessage()], 500);
        }
        
    }

    public function delete(Request $request, $id){
        try{
            $machine =Machine::find($id);

            if(!$machine){
                return response()->json(['error' => 'Machine not found'], 404);
            }

            $machine->delete();
            return response()->json(['message' => 'Machine deleted  successfully']);
        }catch(\Exception $e){
            return response()->json(['error' => 'Failed to fetch machine', $e->getMessage()], 500);
        }
        
    }

    public function statusUpdate(Request $request)
    {
        try{
            $id = $request->id;
            $machine =Machine::find($id);

            if(!$machine){
                return response()->json(['error' => 'Machine not found'], 404);
            }
            $machine->status= !$machine->status;
            $machine->save();

            return response()->json(['message' => 'Machine status updated  successfully']);
        }catch(\Exception $e){
            return response()->json(['error' => 'Failed to fetch  machine', $e->getMessage()], 500);
        }
        
    }
}
