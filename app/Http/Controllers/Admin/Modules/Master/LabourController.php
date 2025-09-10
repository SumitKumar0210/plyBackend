<?php

namespace App\Http\Controllers\Admin\Modules\Master;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Labour;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class LabourController extends Controller
{
    public function getData(Request $request)
    {
        try{
            $labours = Labour::with('department')->orderBy('id','desc')->paginate(10);
            return response()->json($labours);
        }catch(\Exception $e){
            return response()->json(['error' => 'Failed to fetch labour'], 500);
        }
        
    }

    public function search(Request $request)
    {
        try {
            $query = Labour::with('department')->orderBy('id', 'desc');

            if ($request->filled('department')) {
                $query->whereHas('department', function ($q) use ($request) {
                    $q->where('name', 'ILIKE', '%' . $request->department . '%'); 
                });
            }

            if ($request->filled('name')) {
                $query->where('name', 'ILIKE', '%' . $request->name . '%');
            }

            if ($request->filled('par_hour_cost')) {
                $query->where('par_hour_cost', 'ILIKE', '%' . $request->par_hour_cost . '%');
            }

            if ($request->filled('overtime_hourly_rate')) {
                $query->where('overtime_hourly_rate', 'ILIKE', '%' . $request->overtime_hourly_rate . '%');
            }

            if ($request->has('status') && $request->status !== null) {
                $query->where('status', $request->status);
            }

            $labours = $query->paginate(10);
            return response()->json($labours);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to fetch labour',
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
            $labour->department_id = $request->department_id;
            $labour->par_hour_cost = round($request->par_hour_cost, 2);
            $labour->overtime_hourly_rate = round($request->overtime_hourly_rate,2);
            if($request->has('image')){
                $image = $request->file('image');
                $randomName = rand(10000000, 99999999);
                $imageName = time().'_'.$randomName . '.' . $image->getClientOriginalExtension();
                $image->move(public_path('uploads/labour/'), $imageName);
                $labour->image = '/uploads/labour/'.$imageName;

            }
            $labour->created_by = auth()->user()->id;
            $labour->status = $request->status ?? 0;
            $labour->save();
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
            $labour->par_hour_cost = round($request->par_hour_cost, 2);
            $labour->overtime_hourly_rate = round($request->overtime_hourly_rate,2);
            if($request->has('image')){
                $image = $request->file('image');
                $randomName = rand(10000000, 99999999);
                $imageName = time().'_'.$randomName . '.' . $image->getClientOriginalExtension();
                $image->move(public_path('uploads/labour/'), $imageName);
                $labour->image = '/uploads/labour/'.$imageName;

            }
            $labour->created_by = auth()->user()->id;
            $labour->status = $request->status ?? $labour->status;
            $labour->save();

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
