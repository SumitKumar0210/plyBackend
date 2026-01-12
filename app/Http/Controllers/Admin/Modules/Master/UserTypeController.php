<?php

namespace App\Http\Controllers\Admin\Modules\Master;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\UserType;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;


class UserTypeController extends Controller
{
    public function getUserType(Request $request)
    {
        
        try{
            $query = UserType::orderBy('id','desc');
            if ($request->status) {
                $query->where('status', '1');
            }
            
            $userTypes = $query->get();
            $arr = ['data' =>$userTypes];
            return response()->json($arr);
        }catch(\Exception $e){
            return response()->json(['error' => 'Failed to fetch user types'], 500);
        }
        
    }

    public function search(Request $request)
    {
        try{
            $query = UserType::orderBy('id','desc');
            if ($request->has('name') && !empty($request->name)) {
                $query->where('name', 'like', '%' . $request->name . '%');
            }
            if ($request->has('status') && $request->status !== null) {
                $query->where('status', $request->status);
            }
            $userTypes = $query->paginate(10);
            return response()->json($userTypes);
        }catch(\Exception $e){
            return response()->json(['error' => 'Failed to fetch user types'], 500);
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
                    Rule::unique('user_types', 'name')->whereNull('deleted_at'),
                ],
            ]);


            $userType = new UserType();

            $userType->name = $request->name;
            $userType->created_by = auth()->user()->id;
            $userType->status = $request->status ?? 0;
            $userType->save();
            return response()->json(['message' => 'User type created successfully',
                'data' => $userType]);
        }catch(\Exception $e){
            return response()->json(['error' => 'Failed to fetch user types', $e->getMessage()], 500);
        }
        
    }

    public function edit(Request $request, $id)
    {
        try{
            $userType =UserType::find($id);

            if(!$userType){
                return response()->json(['error' => 'User type not found'], 404);
            }
            return response()->json(['message' => 'User type fetch  successfully',
                'data' => $userType]);
        }catch(\Exception $e){
            return response()->json(['error' => 'Failed to fetch user types', $e->getMessage()], 500);
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
                    Rule::unique('user_types', 'name')
                        ->ignore($id) 
                        ->whereNull('deleted_at'), 
                ],
                'status' => 'nullable|in:0,1',
            ]);

            $userType =UserType::find($id);

            if(!$userType){
                return response()->json(['error' => 'User type not found'], 404);
            }
            $userType->name = $request->name;
            $userType->created_by = auth()->user()->id;
            $userType->status = $request->status ?? $userType->status;
            $userType->save();

            return response()->json(['message' => 'User type updated  successfully',
                'data' => $userType]);
        }catch(\Exception $e){
            return response()->json(['error' => 'Failed to fetch user types', $e->getMessage()], 500);
        }
        
    }

    public function delete(Request $request, $id)
    {
        try {
            $userType = UserType::find($id);
    
            if (!$userType) {
                return response()->json([
                    'error' => 'User type not found'
                ], 404);
            }
    
            $userType->delete();
    
            return response()->json([
                'message' => 'User type deleted successfully'
            ], 200);
    
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to delete user type',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function statusUpdate(Request $request)
    {
        try{
            $id = $request->id;
            $userType =UserType::find($id);

            if(!$userType){
                return response()->json(['error' => 'User type not found'], 404);
            }
            $userType->status= $request->status ?? $userType->status;
            $userType->save();

            return response()->json(['message' => 'User type status updated  successfully']);
        }catch(\Exception $e){
            return response()->json(['error' => 'Failed to fetch user types', $e->getMessage()], 500);
        }
        
    }
}
