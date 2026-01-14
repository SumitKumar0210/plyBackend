<?php

namespace App\Http\Controllers\Admin\Modules\Master;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Customer;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class CustomerController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api');

        $this->middleware('permission:customers.read')->only([
            'getData','search'
        ]);

        $this->middleware('permission:customers.create')->only([
            'store'
        ]);

        $this->middleware('permission:customers.update')->only([
            'edit', 'update', 'statusUpdate'
        ]);

        $this->middleware('permission:customers.delete')->only([
            'delete'
        ]);
    }
    
    public function getData(Request $request)
    {
        try {
            $query = Customer::with('state')->orderByDesc('id');
    
            if ($request->filled('status')) {
                $customers = $query->where('status', $request->status)->get();
    
            }
                return response()->json([
                    'data' => $customers,
                    'message' => 'customers fetched successfully!'
                ], 200);
    
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to fetch customers',
                'details' => $e->getMessage(),
            ], 500);
        }
    }


    public function search(Request $request)
    {
        try {
    
            $search = $request->search;

            $customers = Customer::with('state')
                ->when($request->active, function ($q) {
                    $q->where('status', '1');
                })
                ->when($search, function ($q) use ($search) {
                    $q->where(function ($query) use ($search) {
                        $query->where('name', 'ILIKE', "%{$search}%")
                              ->orWhere('mobile', 'ILIKE', "%{$search}%")
                              ->orWhere('email', 'ILIKE', "%{$search}%")
                              ->orWhere('gst_no', 'ILIKE', "%{$search}%")
                              ->orWhere('city', 'ILIKE', "%{$search}%")
                              ->orWhere('address', 'ILIKE', "%{$search}%")
                              ->orWhere('zip_code', 'ILIKE', "%{$search}%");
                    });
                })
                ->orderByDesc('id')
                ->paginate($request->limit ?? 10);

    
            return response()->json([
                'data' => $customers,
                'message' => 'Customers fetched successfully!'
            ], 200);
    
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to fetch customers',
                'details' => $e->getMessage(),
            ], 500);
        }
    }


    public function store(Request $request)
    {
        try{
           $request->validate(
                [
                    'email' => [
                        'required',
                        'string',
                        'max:255',
                        Rule::unique('customers', 'email')->whereNull('deleted_at'),
                    ],
                    'mobile' => 'required|digits:10',
                    'alternate_mobile' => 'nullable|digits:10',
                    'name' => 'required|string|max:255',
                ],
                [
                    'email.required' => 'Email address is required.',
                    'email.string'   => 'Email address must be a valid string.',
                    'email.max'      => 'Email address must not exceed 255 characters.',
                    'email.unique'   => 'This email address is already registered.',
            
                    'mobile.required' => 'Mobile number is required.',
                    'mobile.digits'   => 'Mobile number must be exactly 10 digits.',
            
                    'alternate_mobile.digits' => 'Alternate mobile number must be exactly 10 digits.',
            
                    'name.required' => 'Customer name is required.',
                    'name.string'   => 'Customer name must be a valid string.',
                    'name.max'      => 'Customer name must not exceed 255 characters.',
                ]
            );


            $customers = new Customer();
            $customers->name = $request->name;
            $customers->mobile = $request->mobile;
            $customers->alternate_mobile = $request->alternate_mobile;
            $customers->email = $request->email;
            $customers->address = $request->address;
            $customers->city = $request->city;
            $customers->state_id = $request->state_id;
            $customers->zip_code = $request->zip_code;
            $customers->note = $request->note;
            $customers->gst_no = $request->gst_no;
            $customers->created_by = auth()->user()->id;
            $customers->status = '1';
            $customers->save();
            $customers->load('state');
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
            $customers->gst_no = $request->gst_no;
            $customers->created_by = auth()->user()->id;
            $customers->status = $request->status ?? $customers->status;
            $customers->save();
            $customers->load('state');

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
