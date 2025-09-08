<?php

namespace App\Http\Controllers\Admin\Modules\Master;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Department;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;


class DepartmentController extends Controller
{
    public function getData(Request $request)
    {
        try{
            $departments = Department::orderBy('id','desc')->paginate(10);
            return response()->json($departments);
        }catch(\Exception $e){
            return response()->json(['error' => 'Failed to fetch departments'], 500);
        }
        
    }

    public function search(Request $request)
    {
        try{
            $query = Department::orderBy('id','desc');
            if ($request->has('name') && !empty($request->name)) {
                $query->where('name', 'like', '%' . $request->name . '%');
            }
            if ($request->has('status') && $request->status !== null) {
                $query->where('status', $request->status);
            }
            $departments = $query->paginate(10);
            return response()->json($departments);
        }catch(\Exception $e){
            return response()->json(['error' => 'Failed to fetch departments'], 500);
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
                    Rule::unique('departments', 'name')->whereNull('deleted_at'),
                ],
            ]);

            $departments = new Department();
            $departments->name = $request->name;
            $departments->created_by = auth()->user()->id;
            $departments->status = $request->status ?? 0;
            $departments->save();
            return response()->json(['message' => 'Department created successfully',
                'data' => $departments]);
        }catch(\Exception $e){
            return response()->json(['error' => 'Failed to fetch departments', $e->getMessage()], 500);
        }
        
    }

    public function edit(Request $request, $id)
    {
        try{

            $departments =Department::find($id);

            if(!$departments){
                return response()->json(['error' => 'Department not found'], 404);
            }
            return response()->json(['message' => 'Department fetch  successfully',
                'data' => $departments]);
        }catch(\Exception $e){
            return response()->json(['error' => 'Failed to fetch department', $e->getMessage()], 500);
        }
        
    }

    public function update(Request $request, $id){
        try{

            $request->validate([
                'name' => [
                    'required',
                    'string',
                    'max:255',
                    Rule::unique('departments', 'name')
                        ->ignore($id) 
                        ->whereNull('deleted_at'), 
                ],
                'status' => 'nullable|in:0,1',
            ]);

            $departments =Department::find($id);

            if(!$departments){
                return response()->json(['error' => 'Department not found'], 404);
            }

            $departments->name = $request->name;
            $departments->created_by = auth()->user()->id;
            $departments->status = $request->status ?? $departments->status;
            $departments->save();

            return response()->json(['message' => 'Department updated  successfully',
                'data' => $departments]);
        }catch(\Exception $e){
            return response()->json(['error' => 'Failed to fetch department', $e->getMessage()], 500);
        }
        
    }

    public function delete(Request $request, $id){
        try{

            $departments =Department::find($id);

            if(!$departments){
                return response()->json(['error' => 'Department not found'], 404);
            }
            $departments->delete();

            return response()->json(['message' => 'Department deleted  successfully']);
        }catch(\Exception $e){
            return response()->json(['error' => 'Failed to fetch department', $e->getMessage()], 500);
        }
        
    }

    public function statusUpdate(Request $request)
    {
        try{
            $id = $request->id;
            $department =Department::find($id);

            if(!$department){
                return response()->json(['error' => 'Department not found'], 404);
            }
            $department->status= !$department->status;
            $department->save();

            return response()->json(['message' => 'Department status updated  successfully']);
        }catch(\Exception $e){
            return response()->json(['error' => 'Failed to fetch  department', $e->getMessage()], 500);
        }
        
    }
}
