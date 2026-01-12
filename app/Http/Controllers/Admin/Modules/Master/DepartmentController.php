<?php

namespace App\Http\Controllers\Admin\Modules\Master;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Department;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;


class DepartmentController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api');

        $this->middleware('permission:departments.read')->only([
            'getData', 'search'
        ]);

        $this->middleware('permission:departments.create')->only([
            'store'
        ]);

        $this->middleware('permission:departments.update')->only([
            'edit', 'update', 'statusUpdate','sequenceUpdate'
        ]);

        $this->middleware('permission:departments.delete')->only([
            'delete'
        ]);
        
    }
    
    public function getData(Request $request)
    {
        try{
            $query = Department::orderBy('sequence','desc');

            if ($request->status) {
                $query->where('status', '1');
            }
            
            $departments = $query->get();
            $arr = ['data' =>$departments];
            return response()->json($arr);
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
            
            $sequence = Department::orderBy('sequence','desc')->first();

            $department = new Department();
            $department->name = $request->name;
            $department->color = $request->color;
            $department->sequence = $sequence->sequence +1;
            $department->created_by = auth()->user()->id;
            $department->status = $request->status ?? 1;
            $department->save();
            return response()->json(['message' => 'Department created successfully',
                'data' => $department]);
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
            $departments->color = $request->color;
            $departments->created_by = auth()->user()->id;
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
    
    public function sequenceUpdate(Request $request)
    {
        try {
            $request->validate([
            'id' => 'required|integer|exists:departments,id',
            'sequence' => [
                'required',
                'integer',
                'min:1',
                Rule::unique('departments', 'sequence')
            ->ignore($request->id)
            ->whereNull('deleted_at')
            ],
        ]);
    
            $category = Department::find($request->id);
            if (!$category) {
                return response()->json(['error' => 'Category not found'], 404);
            }
                $category->sequence = $request->sequence;
                $category->save();
            
    
            return response()->json([
                'message' => 'Category sequence updated successfully',
                'data' => $category->fresh()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to update sequence',
                'details' => $e->getMessage(),
            ], 500);
        }
    }
}
