<?php

namespace App\Http\Controllers\Admin\Modules\Master;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\SalesUser;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class SalesUserController extends Controller
{
    public function getData(Request $request)
    {
        try{
            $sales_users = SalesUser::orderBy('id','desc')->paginate(10);
            return response()->json($sales_users);
        }catch(\Exception $e){
            return response()->json(['error' => 'Failed to fetch sales users'], 500);
        }
        
    }

    public function search(Request $request)
    {
        try {
            $query = SalesUser::orderBy('id', 'desc');

            if ($request->has('name') && !empty($request->name)) {
                $query->where('name', 'ILIKE', '%' . $request->name . '%'); 
            }

            if ($request->has('email') && !empty($request->email)) {
                $query->where('email', 'ILIKE', '%' . $request->email . '%');
            }

            if ($request->has('mobile') && !empty($request->mobile)) {
                $query->where('mobile', 'LIKE', '%' . $request->mobile . '%'); 
            }

            if ($request->has('status') && $request->status !== null) {
                $query->where('status', $request->status);
            }

            $sales_users = $query->paginate(10);
            return response()->json($sales_users);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to fetch sales users'], 500);
        }
    }

    public function store(Request $request)
    {
        try{
            $request->validate([
                'email' => [
                    'required',
                    'string',
                    'max:255',
                    Rule::unique('sales_users', 'email')->whereNull('deleted_at'),
                ],
                'mobile' => 'required|digits:10',
                'name' => 'required|string|max:255',
            ]);

            $sales_user = new SalesUser();
            $sales_user->name = $request->name;
            $sales_user->mobile = $request->mobile;
            $sales_user->email = $request->email;
            $sales_user->address = $request->address;
            $sales_user->city = $request->city;
            // $sales_user->state_id = $request->state_id;
            $sales_user->zip_code = $request->zip_code;
            $sales_user->created_by = auth()->user()->id;
            $sales_user->status = $request->status ?? 0;
            $sales_user->save();
            return response()->json(['message' => 'Sales user created successfully',
                'data' => $sales_user]);
        }catch(\Exception $e){
            return response()->json(['error' => 'Failed to store sales user', $e->getMessage()], 500);
        }
        
    }

    public function edit(Request $request, $id)
    {
        try{

            $sales_user =SalesUser::find($id);

            if(!$sales_user){
                return response()->json(['error' => 'Sales user not found'], 404);
            }
            return response()->json(['message' => 'Sales user fetch  successfully',
                'data' => $sales_user]);
        }catch(\Exception $e){
            return response()->json(['error' => 'Failed to fetch sales user', $e->getMessage()], 500);
        }
        
    }

    public function update(Request $request, $id){
        try{

            $request->validate([
                'name' => 'required|string|max:255',
                'email' => [
                    'required',
                    'email',
                    'max:255',
                    Rule::unique('sales_users', 'email')->ignore($id)->whereNull('deleted_at'),
                ],
                'mobile' => 'required|digits:10',
                'alternate_mobile' => 'nullable|digits:10',
                'status' => 'nullable|in:0,1',
            ]);

            $sales_user =SalesUser::find($id);

            if(!$sales_user){
                return response()->json(['error' => 'Sales user not found'], 404);
            }

            $sales_user->name = $request->name;
            $sales_user->mobile = $request->mobile;
            $sales_user->email = $request->email;
            $sales_user->address = $request->address;
            $sales_user->city = $request->city;
            $sales_user->state_id = $request->state_id;
            $sales_user->zip_code = $request->zip_code;
            $sales_user->created_by = auth()->user()->id;
            $sales_user->status = $request->status ?? $sales_user->status;
            $sales_user->save();

            return response()->json(['message' => 'Sales user updated  successfully',
                'data' => $sales_user]);
        }catch(\Exception $e){
            return response()->json(['error' => 'Failed to update sales user', $e->getMessage()], 500);
        }
        
    }

    public function delete(Request $request, $id){
        try{

            $sales_user =SalesUser::find($id);

            if(!$sales_user){
                return response()->json(['error' => 'Sales user not found'], 404);
            }
            $sales_user->delete();

            return response()->json(['message' => 'Sales user deleted  successfully']);
        }catch(\Exception $e){
            return response()->json(['error' => 'Failed to delete sales user', $e->getMessage()], 500);
        }
        
    }

    public function statusUpdate(Request $request)
    {
        try{
            $id = $request->id;
            $sales_user =SalesUser::find($id);

            if(!$sales_user){
                return response()->json(['error' => 'Sales user not found'], 404);
            }
            $sales_user->status= !$sales_user->status;
            $sales_user->save();

            return response()->json(['message' => 'Sales user status updated  successfully']);
        }catch(\Exception $e){
            return response()->json(['error' => 'Failed to fetch  sales user', $e->getMessage()], 500);
        }
        
    }
}
