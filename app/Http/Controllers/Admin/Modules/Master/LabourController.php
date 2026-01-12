<?php

namespace App\Http\Controllers\Admin\Modules\Master;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Labour;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class LabourController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api');

        $this->middleware('permission:labours.read')->only([
            'getData','search'
        ]);

        $this->middleware('permission:labours.create')->only([
            'store'
        ]);

        $this->middleware('permission:labours.update')->only([
            'edit', 'update', 'statusUpdate'
        ]);

        $this->middleware('permission:labours.delete')->only([
            'delete'
        ]);
    }
    
    public function getData(Request $request)
    {
        try{
            $query = Labour::with('department')->orderBy('id','desc');
            if ($request->has('status') && $request->status !== '') {
                $query->where('status', $request->status);
            }
            $labours =$query->get();
            $arr = ['data' => $labours];
            return response()->json($arr);
        }catch(\Exception $e){
            return response()->json(['error' => 'Failed to fetch labour'], 500);
        }
        
    }

    public function search(Request $request)
    {
        try {
            $search = $request->search;
    
            $labours = Labour::with('department')
                ->when($request->active, function ($q) {
                    $q->where('status', '1');
                })
                ->when($search, function ($q) use ($search) {
                    $q->where(function ($query) use ($search) {
                        $query->where('name', 'ILIKE', "%{$search}%");
    
                        // Search inside department table
                        $query->orWhereHas('department', function ($q1) use ($search) {
                            $q1->where('name', 'ILIKE', "%{$search}%");
                        });
                    });
                })
                ->orderByDesc('id')
                ->paginate($request->limit ?? 10);
    
            return response()->json([
                'data'    => $labours,
                'message' => 'Labours fetched successfully!'
            ], 200);
    
        } catch (\Exception $e) {
            return response()->json([
                'error'   => 'Failed to fetch labours',
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
                    Rule::unique('labours', 'name')->whereNull('deleted_at'),
                ],
            ]);

            $labour = new Labour();

            $labour->name = $request->name;
            $labour->document_number = $request->document_number;
            $labour->other_document_name = $request->other_document_name;
            $labour->document_type = $request->document_type;
            $labour->dob = $request->dob;
            $labour->department_id = $request->department_id;
            $labour->shift_id = $request->shift_id;
            $labour->per_hour_cost = round($request->per_hour_cost, 2);
            $labour->overtime_hourly_rate = round($request->overtime_hourly_rate,2);
            if($request->has('image')){
                $image = $request->file('image');
                $randomName = rand(10000000, 99999999);
                $imageName = time().'_'.$randomName . '.' . $image->getClientOriginalExtension();
                $image->move(public_path('uploads/labour/'), $imageName);
                $labour->image = '/uploads/labour/'.$imageName;

            }
            if($request->has('document_file')){
                $image = $request->file('document_file');
                $randomName = rand(10000000, 99999999);
                $imageName = time().'_'.$randomName . '.' . $image->getClientOriginalExtension();
                $image->move(public_path('uploads/labour/'), $imageName);
                $labour->document_file = '/uploads/labour/'.$imageName;

            }
            $labour->created_by = auth()->user()->id;
            $labour->status = $request->status ?? 1;
            $labour->save();
            $labour->load('department');
            return response()->json(['message' => 'Labour created successfully',
                'data' => $labour]);
        }catch(\Exception $e){
            return response()->json(['error' => 'Failed to store labour', $e->getMessage()], 500);
        }
        
    }

    public function edit(Request $request, $id)
    {
        try{
            $labour =Labour::find($id);

            if(!$labour){
                return response()->json(['error' => 'Labour not found'], 404);
            }
            return response()->json(['message' => 'Labour fetch  successfully',
                'data' => $labour]);
        }catch(\Exception $e){
            return response()->json(['error' => 'Failed to fetch labour', $e->getMessage()], 500);
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
                    Rule::unique('labours', 'name')
                        ->ignore($id) 
                        ->whereNull('deleted_at'), 
                ],
                'status' => 'nullable|in:0,1',
            ]);
            $labour =Labour::find($id);

            if(!$labour){
                return response()->json(['error' => 'Labour not found'], 404);
            }
            $labour->name = $request->name;
            $labour->department_id = $request->department_id;
            $labour->shift_id = $request->shift_id;
            $labour->document_number = $request->document_number;
            $labour->other_document_name = $request->other_document_name;
            $labour->document_type = $request->document_type;
            $labour->dob = $request->dob;
            $labour->per_hour_cost = round($request->per_hour_cost, 2);
            $labour->overtime_hourly_rate = round($request->overtime_hourly_rate,2);
            if($request->has('image') && $request->file('image')){
                $image = $request->file('image');
                $randomName = rand(10000000, 99999999);
                $imageName = time().'_'.$randomName . '.' . $image->getClientOriginalExtension();
                $image->move(public_path('uploads/labour/'), $imageName);
                $labour->image = '/uploads/labour/'.$imageName;

            }
            if($request->has('document_file') && $request->file('document_file')){
                $image = $request->file('document_file');
                $randomName = rand(10000000, 99999999);
                $imageName = time().'_'.$randomName . '.' . $image->getClientOriginalExtension();
                $image->move(public_path('uploads/labour/'), $imageName);
                $labour->document_file = '/uploads/labour/'.$imageName;

            }
            $labour->created_by = auth()->user()->id;
            $labour->status = $request->status ?? $labour->status;
            $labour->save();
            $labour->load('department');

            return response()->json(['message' => 'Labour updated  successfully',
                'data' => $labour]);
        }catch(\Exception $e){
            return response()->json(['error' => 'Failed to fetch labour', $e->getMessage()], 500);
        }
        
    }

    public function delete(Request $request, $id){
        try{
            $labour =Labour::find($id);

            if(!$labour){
                return response()->json(['error' => 'Labour not found'], 404);
            }

            $labour->delete();
            return response()->json(['message' => 'Labour deleted  successfully']);
        }catch(\Exception $e){
            return response()->json(['error' => 'Failed to fetch labour', $e->getMessage()], 500);
        }
        
    }

    public function statusUpdate(Request $request)
    {
        try{
            $id = $request->id;
            $labour =Labour::find($id);

            if(!$labour){
                return response()->json(['error' => 'Labour not found'], 404);
            }
            $labour->status= !$labour->status;
            $labour->save();

            return response()->json(['message' => 'Labour status updated  successfully']);
        }catch(\Exception $e){
            return response()->json(['error' => 'Failed to fetch  labour', $e->getMessage()], 500);
        }
        
    }
}
