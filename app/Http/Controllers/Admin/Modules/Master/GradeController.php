<?php

namespace App\Http\Controllers\Admin\Modules\Master;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Grade;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;


class GradeController extends Controller
{
    public function getData(Request $request)
    {
        try{
            $grades = Grade::orderBy('id','desc')->get();
            $arr = [ 'data' => $grades];
            return response()->json($arr);
        }catch(\Exception $e){
            return response()->json(['error' => 'Failed to fetch grades'], 500);
        }
        
    }

    public function search(Request $request)
    {
        try {
            $query = Grade::orderBy('id', 'desc');

            if ($request->filled('name')) {
                $query->where('name', 'ILIKE', '%' . $request->name . '%');
            }

            if ($request->has('status') && $request->status !== null) {
                $query->where('status', $request->status);
            }

            $grades = $query->paginate(10);
            return response()->json($grades);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to fetch grades',
                'message' => $e->getMessage()
            ], 500);
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
                    Rule::unique('grades', 'name')->whereNull('deleted_at'),
                ],
            ]);

            $grade = new Grade();
            $grade->name = $request->name;
            $grade->created_by = auth()->user()->id;
            $grade->status = $request->status ?? 1;
            $grade->save();
            return response()->json(['message' => 'Grade created successfully',
                'data' => $grade]);
        }catch(\Exception $e){
            return response()->json(['error' => 'Failed to fetch grade', $e->getMessage()], 500);
        }
        
    }

    public function edit(Request $request, $id)
    {
        try{
            $grade =Grade::find($id);

            if(!$grade){
                return response()->json(['error' => 'Grade not found'], 404);
            }
            return response()->json(['message' => 'Grade fetch  successfully',
                'data' => $grade]);
        }catch(\Exception $e){
            return response()->json(['error' => 'Failed to fetch grade', $e->getMessage()], 500);
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
                    Rule::unique('grades', 'name')
                        ->ignore($id) 
                        ->whereNull('deleted_at'), 
                ],
                'status' => 'nullable|in:0,1',
            ]);
            $grade =Grade::find($id);

            if(!$grade){
                return response()->json(['error' => 'Grade not found'], 404);
            }
            $grade->name = $request->name;
            $grade->created_by = auth()->user()->id;
            $grade->status = $request->status ?? $grade->status;
            $grade->save();

            return response()->json(['message' => 'Grade updated  successfully',
                'data' => $grade]);
        }catch(\Exception $e){
            return response()->json(['error' => 'Failed to fetch grade', $e->getMessage()], 500);
        }
        
    }

    public function delete(Request $request, $id){
        try{
            $grade =Grade::find($id);

            if(!$grade){
                return response()->json(['error' => 'Grade not found'], 404);
            }

            $grade->delete();
            return response()->json(['message' => 'Grade deleted  successfully']);
        }catch(\Exception $e){
            return response()->json(['error' => 'Failed to fetch grade', $e->getMessage()], 500);
        }
        
    }

    public function statusUpdate(Request $request)
    {
        try{
            $id = $request->id;
            $grade =Grade::find($id);

            if(!$grade){
                return response()->json(['error' => 'Grade not found'], 404);
            }
            $grade->status= !$grade->status;
            $grade->save();

            return response()->json(['message' => 'Grade status updated  successfully']);
        }catch(\Exception $e){
            return response()->json(['error' => 'Failed to fetch  grade', $e->getMessage()], 500);
        }
        
    }
}
