<?php

namespace App\Http\Controllers\Admin\Modules\Master;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Customer;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class CustomerController extends Controller
{
    public function getData(Request $request)
    {
        try{
            $customers = Customer::orderBy('id','desc')->paginate(10);
            return response()->json($customers);
        }catch(\Exception $e){
            return response()->json(['error' => 'Failed to fetch customers'], 500);
        }
        
    }

    public function search(Request $request)
    {
        try {
            $query = Customer::orderBy('id', 'desc');

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

            $customers = $query->paginate(10);
            return response()->json($customers);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to fetch customers'], 500);
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
                    Rule::unique('customers', 'email')->whereNull('deleted_at'),
                ],
                'mobile' => 'required|digits:10',
                'alternate_mobile' => 'nullable|digits:10',
                'name' => 'required|string|max:255',
            ]);

            $customers = new Customer();
            $customers->name = $request->name;
            $customers->mobile = $request->mobile;
            $customers->alternate_mobile = $request->alternate_mobile;
            $customers->email = $request->email;
            $customers->address = $request->address;
            $customers->city = $request->city;
            // $customers->state_id = $request->state_id;
            $customers->zip_code = $request->zip_code;
            $customers->note = $request->note;
            $customers->created_by = auth()->user()->id;
            $customers->status = $request->status ?? 0;
            $customers->save();
            return response()->json(['message' => 'Customer created successfully',
                'data' => $customers]);
        }catch(\Exception $e){
            return response()->json(['error' => 'Failed to store customer', $e->getMessage()], 500);
        }
        
    }

    public function edit(Request $request, $id)
    {
        try{

            $customers =Customer::find($id);

            if(!$customers){
                return response()->json(['error' => 'Customer not found'], 404);
            }
            return response()->json(['message' => 'Customer fetch  successfully',
                'data' => $customers]);
        }catch(\Exception $e){
            return response()->json(['error' => 'Failed to fetch customer', $e->getMessage()], 500);
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
                    Rule::unique('customers', 'email')->ignore($id)->whereNull('deleted_at'),
                ],
                'mobile' => 'required|digits:10',
                'alternate_mobile' => 'nullable|digits:10',
                'status' => 'nullable|in:0,1',
            ]);

            $customers =Customer::find($id);

            if(!$customers){
                return response()->json(['error' => 'Customer not found'], 404);
            }

            $customers->name = $request->name;
            $customers->mobile = $request->mobile;
            $customers->alternate_mobile = $request->alternate_mobile;
            $customers->email = $request->email;
            $customers->address = $request->address;
            $customers->city = $request->city;
            $customers->state_id = $request->state_id;
            $customers->zip_code = $request->zip_code;
            $customers->note = $request->note;
            $customers->created_by = auth()->user()->id;
            $customers->status = $request->status ?? $customers->status;
            $customers->save();

            return response()->json(['message' => 'Customer updated  successfully',
                'data' => $customers]);
        }catch(\Exception $e){
            return response()->json(['error' => 'Failed to update customer', $e->getMessage()], 500);
        }
        
    }

    public function delete(Request $request, $id){
        try{

            $customers =Customer::find($id);

            if(!$customers){
                return response()->json(['error' => 'Customer not found'], 404);
            }
            $customers->delete();

            return response()->json(['message' => 'Customer deleted  successfully']);
        }catch(\Exception $e){
            return response()->json(['error' => 'Failed to delete customer', $e->getMessage()], 500);
        }
        
    }

    public function statusUpdate(Request $request)
    {
        try{
            $id = $request->id;
            $customer =Customer::find($id);

            if(!$customer){
                return response()->json(['error' => 'Customer not found'], 404);
            }
            $customer->status= !$customer->status;
            $customer->save();

            return response()->json(['message' => 'Customer status updated  successfully']);
        }catch(\Exception $e){
            return response()->json(['error' => 'Failed to fetch  customer', $e->getMessage()], 500);
        }
        
    }
}
