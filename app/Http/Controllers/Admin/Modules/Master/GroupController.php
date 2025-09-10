<?php

namespace App\Http\Controllers\Admin\Modules\Master;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Group;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;


class GroupController extends Controller
{
    public function getData(Request $request)
    {
        try{
            $group = Group::orderBy('id','desc')->paginate(10);
            return response()->json($group);
        }catch(\Exception $e){
            return response()->json(['error' => 'Failed to fetch groups'], 500);
        }
        
    }

    public function search(Request $request)
    {
        try {
            $query = Group::orderBy('id', 'desc');

            if ($request->filled('name')) {
                $query->where('name', 'ILIKE', '%' . $request->name . '%');
            }

            if ($request->has('status') && $request->status !== null) {
                $query->where('status', $request->status);
            }

            $group = $query->paginate(10);
            return response()->json($group);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to fetch groups',
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
                    Rule::unique('groups', 'name')->whereNull('deleted_at'),
                ],
            ]);

            $group = new Group();
            $group->name = $request->name;
            $group->created_by = auth()->user()->id;
            $group->status = $request->status ?? 0;
            $group->save();
            return response()->json(['message' => 'Group created successfully',
                'data' => $group]);
        }catch(\Exception $e){
            return response()->json(['error' => 'Failed to fetch group', $e->getMessage()], 500);
        }
        
    }

    public function edit(Request $request, $id)
    {
        try{
            $group =Group::find($id);

            if(!$group){
                return response()->json(['error' => 'Group not found'], 404);
            }
            return response()->json(['message' => 'Group fetch  successfully',
                'data' => $group]);
        }catch(\Exception $e){
            return response()->json(['error' => 'Failed to fetch group', $e->getMessage()], 500);
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
                    Rule::unique('groups', 'name')
                        ->ignore($id) 
                        ->whereNull('deleted_at'), 
                ],
                'status' => 'nullable|in:0,1',
            ]);
            $group =Group::find($id);

            if(!$group){
                return response()->json(['error' => 'Group not found'], 404);
            }
            $group->name = $request->name;
            $group->created_by = auth()->user()->id;
            $group->status = $request->status ?? $group->status;
            $group->save();

            return response()->json(['message' => 'Group updated  successfully',
                'data' => $group]);
        }catch(\Exception $e){
            return response()->json(['error' => 'Failed to fetch group', $e->getMessage()], 500);
        }
        
    }

    public function delete(Request $request, $id){
        try{
            $group =Group::find($id);

            if(!$group){
                return response()->json(['error' => 'Group not found'], 404);
            }

            $group->delete();
            return response()->json(['message' => 'Group deleted  successfully']);
        }catch(\Exception $e){
            return response()->json(['error' => 'Failed to fetch group', $e->getMessage()], 500);
        }
        
    }

    public function statusUpdate(Request $request)
    {
        try{
            $id = $request->id;
            $group =Group::find($id);

            if(!$group){
                return response()->json(['error' => 'Group not found'], 404);
            }
            $group->status= !$group->status;
            $group->save();

            return response()->json(['message' => 'Group status updated  successfully']);
        }catch(\Exception $e){
            return response()->json(['error' => 'Failed to fetch  group', $e->getMessage()], 500);
        }
        
    }
}
