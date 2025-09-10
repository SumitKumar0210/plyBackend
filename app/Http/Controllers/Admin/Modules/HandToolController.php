<?php

namespace App\Http\Controllers\Admin\modules;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\HandTool;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class HandToolController extends Controller
{
    public function getData(Request $request)
    {
        try{
            $handTools = HandTool::with('material', 'labour', 'department')->orderBy('id','desc')->paginate(10);
            return response()->json($handTools);
        }catch(\Exception $e){
            return response()->json(['error' => 'Failed to fetch hand tools'], 500);
        }
        
    }

    public function search(Request $request)
    {
        try {
            $query = HandTool::with('material', 'labour', 'department')->orderBy('id', 'desc');

            if ($request->filled('material')) {
                $query->whereHas('material', function ($q) use ($request) {
                    $q->where('name', 'ILIKE', '%' . $request->material . '%'); 
                });
            }

            if ($request->filled('labour')) {
                $query->whereHas('labour', function ($q) use ($request) {
                    $q->where('name', 'ILIKE', '%' . $request->labour . '%'); 
                });
            }

            if ($request->filled('department')) {
                $query->whereHas('department', function ($q) use ($request) {
                    $q->where('name', 'ILIKE', '%' . $request->department . '%'); 
                });
            }

            if ($request->filled('no_of_item')) {
                $query->where('no_of_item', 'ILIKE', '%' . $request->no_of_item . '%');
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
                'labour_id' => [
                    'required',
                    Rule::unique('hand_tools', 'labour_id')->whereNull('deleted_at'),
                ],
            ]);

            $handTool = new HandTool();

            $handTool->material_id = $request->material_id;
            $handTool->labour_id = $request->labour_id;
            $handTool->department_id = $request->department_id;
            $handTool->no_of_item = $request->no_of_item;
            $handTool->status = $request->status ?? 0;
            $handTool->save();
            return response()->json(['message' => 'Hand tool created successfully',
                'data' => $handTool]);
        }catch(\Exception $e){
            return response()->json(['error' => 'Failed to store hand tool', $e->getMessage()], 500);
        }
        
    }

    public function edit(Request $request, $id)
    {
        try{
            $handTool =HandTool::find($id);

            if(!$handTool){
                return response()->json(['error' => 'Hand tool not found'], 404);
            }
            return response()->json(['message' => 'Hand tool fetch  successfully',
                'data' => $handTool]);
        }catch(\Exception $e){
            return response()->json(['error' => 'Failed to fetch hand tool', $e->getMessage()], 500);
        }
        
    }

    public function update(Request $request, $id)
    {
        try{
            $request->validate([
                'labour_id' => [
                    'required',
                    Rule::unique('hand_tools', 'labour_id')
                        ->ignore($id) 
                        ->whereNull('deleted_at'), 
                ],
                'status' => 'nullable|in:0,1',
            ]);
            $handTool =HandTool::find($id);

            if(!$handTool){
                return response()->json(['error' => 'Hand tool not found'], 404);
            }
            $handTool->material_id = $request->material_id;
            $handTool->labour_id = $request->labour_id;
            $handTool->department_id = $request->department_id;
            $handTool->no_of_item = $request->no_of_item;
            $handTool->status = $request->status ?? $handTool->status;
            $handTool->save();

            return response()->json(['message' => 'Hand tool updated  successfully',
                'data' => $handTool]);
        }catch(\Exception $e){
            return response()->json(['error' => 'Failed to fetch hand tool', $e->getMessage()], 500);
        }
        
    }

    public function delete(Request $request, $id){
        try{
            $handTool =HandTool::find($id);

            if(!$handTool){
                return response()->json(['error' => 'Hand tool not found'], 404);
            }

            $handTool->delete();
            return response()->json(['message' => 'Hand tool deleted  successfully']);
        }catch(\Exception $e){
            return response()->json(['error' => 'Failed to fetch hand tool', $e->getMessage()], 500);
        }
        
    }

    public function statusUpdate(Request $request)
    {
        try{
            $id = $request->id;
            $handTool =HandTool::find($id);

            if(!$handTool){
                return response()->json(['error' => 'Hand tool not found'], 404);
            }
            $handTool->status= !$handTool->status;
            $handTool->save();

            return response()->json(['message' => 'Hand tool status updated  successfully']);
        }catch(\Exception $e){
            return response()->json(['error' => 'Failed to fetch  hand tool', $e->getMessage()], 500);
        }
        
    }
}
