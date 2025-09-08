<?php

namespace App\Http\Controllers\Admin\Modules\Master;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Vendor;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;


class VendorController extends Controller
{
    public function getData(Request $request)
    {
        try{
            $vendors = Vendor::orderBy('id','desc')->paginate(10);
            return response()->json($vendors);
        }catch(\Exception $e){
            return response()->json(['error' => 'Failed to fetch vendors'], 500);
        }
        
    }

    public function search(Request $request)
    {
        try {
            $query = Vendor::orderBy('id', 'desc');

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

            $vendors = $query->paginate(10);
            return response()->json($vendors);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to fetch vendors'], 500);
        }
    }


    public function store(Request $request)
    {
        try{
            $request->validate([
                'name' => 'required|string|max:255',
                'email' => [
                    'required',
                    'email',
                    'max:255',
                    Rule::unique('vendors', 'email')->whereNull('deleted_at'),
                ],
                'mobile' => 'required|digits:10',
            ]);

            $vendor = new Vendor();
            $vendor->name = $request->name;
            $vendor->mobile = $request->mobile;
            $vendor->email = $request->email;
            $vendor->gst = $request->gst;
            $vendor->category_id = $request->category_id;
            $vendor->created_by = auth()->user()->id;
            $vendor->status = $request->status ?? 0;
            $vendor->save();
            return response()->json(['message' => 'Vendor created successfully',
                'data' => $vendor]);
        }catch(\Exception $e){
            return response()->json(['error' => 'Failed to store vendor', $e->getMessage()], 500);
        }
        
    }

    public function edit(Request $request, $id)
    {
        try{

            $vendor =Vendor::find($id);

            if(!$vendor){
                return response()->json(['error' => 'Vendor not found'], 404);
            }
            return response()->json(['message' => 'Vendor fetch  successfully',
                'data' => $vendor]);
        }catch(\Exception $e){
            return response()->json(['error' => 'Failed to fetch vendor', $e->getMessage()], 500);
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
                    Rule::unique('vendors', 'email')->ignore($id)->whereNull('deleted_at'),
                ],
                'mobile' => 'required|digits:10',
                'status' => 'nullable|in:0,1',
            ]);

            $vendor =Vendor::find($id);

            if(!$vendor){
                return response()->json(['error' => 'Vendor not found'], 404);
            }

            $vendor->name = $request->name;
            $vendor->mobile = $request->mobile;
            $vendor->email = $request->email;
            $vendor->gst = $request->gst;
            $vendor->category_id = $request->category_id ?? $vendor->category_id;
            $vendor->created_by = auth()->user()->id;
            $vendor->status = $request->status ?? $vendor->status;
            $vendor->save();

            return response()->json(['message' => 'Vendor updated  successfully',
                'data' => $vendor]);
        }catch(\Exception $e){
            return response()->json(['error' => 'Failed to update vendor', $e->getMessage()], 500);
        }
        
    }

    public function delete(Request $request, $id){
        try{

            $vendor =Vendor::find($id);

            if(!$vendor){
                return response()->json(['error' => 'Vendor not found'], 404);
            }
            $vendor->delete();

            return response()->json(['message' => 'Vendor deleted  successfully']);
        }catch(\Exception $e){
            return response()->json(['error' => 'Failed to delete vendor', $e->getMessage()], 500);
        }
        
    }

    public function statusUpdate(Request $request)
    {
        try{
            $id = $request->id;
            $vendor =Vendor::find($id);

            if(!$vendor){
                return response()->json(['error' => 'Vendor not found'], 404);
            }
            $vendor->status= !$vendor->status;
            $vendor->save();

            return response()->json(['message' => 'Vendor status updated  successfully']);
        }catch(\Exception $e){
            return response()->json(['error' => 'Failed to fetch  vendor', $e->getMessage()], 500);
        }
        
    }
}

