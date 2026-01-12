<?php

namespace App\Http\Controllers\Admin\Modules\Master;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Vendor;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;


class VendorController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api');

        $this->middleware('permission:vendors.read|vendor_lists.read')->only([
            'getData', 'search'
        ]);

        $this->middleware('permission:vendors.create')->only([
            'store'
        ]);

        $this->middleware('permission:vendors.update')->only([
            'edit', 'update', 'statusUpdate'
        ]);

        $this->middleware('permission:vendors.delete')->only([
            'delete'
        ]);
        
        $this->middleware('permission:vendor_invoices.read')->only([
            'getLedgerData'
        ]);
        
    }
    
    // public function getData(Request $request)
    // {
    //     try{
    //         $query = Vendor::with('category')->orderBy('id','desc');
            
    //         if($request->search){
    //             $query->where('name','ILIKE', '%'.$request->search.'%');
    //             $query->where('mobile','ILIKE', '%'.$request->search.'%');
    //             $query->where('email','ILIKE', '%'.$request->search.'%');
    //             $query->where('gst','ILIKE', '%'.$request->search.'%');
    //         }
    //         if($request->status == '1'){
    //             $query->where('status', $request->status);
    //             $vendors = $query->get();
    //         }
    //         $arr = ['data' =>$vendors];
    //         return response()->json($arr);
    //     }catch(\Exception $e){
    //         return response()->json(['error' => 'Failed to fetch vendors'], 500);
    //     }
        
    // }
    public function getData(Request $request)
    {
        try {
            $query = Vendor::with('category','state')->orderBy('id', 'desc');
    
            // Search Filter
            if (!empty($request->search)) {
                $search = $request->search;
    
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'ILIKE', "%$search%")
                      ->orWhere('mobile', 'ILIKE', "%$search%")
                      ->orWhere('email', 'ILIKE', "%$search%")
                      ->orWhere('gst', 'ILIKE', "%$search%");
                });
            }
    
            // Status = 1 → return only status 1 records
            if ($request->status == '1') {
                $query->where('status', 1);
                return response()->json([
                'data'  => $query->get(),
            ]);
            }
    
            // 📄 Pagination (default: 10)
            $perPage = $request->per_page ?? 10;
    
            $vendors = $query->paginate($perPage);
    
            return response()->json([
                'data'  => $vendors->items(),
                'total' => $vendors->total(),
                'page'  => $vendors->currentPage(),
                'per_page' => $vendors->perPage(),
            ]);
            
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to fetch vendors: ' . $e->getMessage()], 500);
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
            $vendor->terms = $request->terms;
            $vendor->created_by = auth()->user()->id;
            $vendor->zip_code = $request->zip_code;
            $vendor->address = $request->address;
            $vendor->city = $request->city;
            $vendor->state_id = $request->state_id;
            $vendor->status = $request->status ?? 1;
            $vendor->save();
            $vendor->load('category');
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
            $vendor->zip_code = $request->zip_code;
            $vendor->address = $request->address;
            $vendor->city = $request->city;
            $vendor->state_id = $request->state_id;            $vendor->terms = $request->terms;
            $vendor->category_id = $request->category_id ?? $vendor->category_id;
            $vendor->created_by = auth()->user()->id;
            $vendor->status = $request->status ?? $vendor->status;
            $vendor->save();
            $vendor->load('category');

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
    
    public function getLedgerData(Request $request){
        try{
            $data = Vendor::with('inwards','state')->find($request->id);
            return response()->json(['data' => $data]);
        }catch(\Exception $e){
            return response()->json(['error' => 'Failed to fetch  vendor', $e->getMessage()], 500);
            
        }
    }
}

