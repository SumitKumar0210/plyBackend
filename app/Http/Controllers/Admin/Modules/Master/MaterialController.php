<?php

namespace App\Http\Controllers\Admin\Modules\Master;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Material;
use App\Models\MaterialLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use DB;


class MaterialController extends Controller
{
    
    public function __construct()
    {
        $this->middleware('auth:api');

        $this->middleware('permission:materials.read')->only([
            'getData', 'search'
        ]);

        $this->middleware('permission:materials.create')->only([
            'store'
        ]);

        $this->middleware('permission:materials.update')->only([
            'edit', 'update', 'statusUpdate'
        ]);

        $this->middleware('permission:materials.delete')->only([
            'delete'
        ]);
    }
    
    public function getData(Request $request)
    {
        try{
            $query = Material::with('category', 'group','unitOfMeasurement')->orderBy('id','desc');
            if($request->status == '1'){
                $query->where('status', $request->status);
            }
            $materials = $query->get();
            $arr = [ 'data' => $materials];
            return response()->json($arr);
        }catch(\Exception $e){
            return response()->json(['error' => 'Failed to fetch materials'.$e->getMessage()], 500);
        }
        
    }

    public function search(Request $request)
    {
        try {
            dd($request->all());
            $query = Material::with('category', 'group','unitOfMeasurement')->orderBy('id', 'desc');

            if ($request->filled('category')) {
                $query->whereHas('category', function ($q) use ($request) {
                    $q->where('name', 'ILIKE', '%' . $request->category . '%'); 
                });
            }

            if ($request->filled('group')) {
                $query->whereHas('group', function ($q) use ($request) {
                    $q->where('name', 'ILIKE', '%' . $request->group . '%'); 
                });
            }

            if ($request->filled('unitOfMeasurement')) {
                $query->whereHas('unitOfMeasurement', function ($q) use ($request) {
                    $q->where('name', 'ILIKE', '%' . $request->unitOfMeasurement . '%'); 
                });
            }

            if ($request->filled('opening_stock')) {
                $query->where('opening_stock', 'ILIKE', '%' . $request->opening_stock . '%');
            }

            if ($request->filled('urgently_required')) {
                $query->where('urgently_required', 'ILIKE', '%' . $request->urgently_required . '%');
            }
            
            if ($request->filled('size')) {
                $query->where('size', 'ILIKE', '%' . $request->size . '%');
            }
            if ($request->filled('hsn_code')) {
                $query->where('hsn_code', 'ILIKE', '%' . $request->hsn_code . '%');
            }

            if ($request->filled('tag')) {
                $query->where('tag', 'ILIKE', '%' . $request->tag . '%');
            }

            if ($request->filled('price')) {
                $query->where('price', 'ILIKE', '%' . $request->price . '%');
            }

            if ($request->has('status') && $request->status !== null) {
                $query->where('status', $request->status);
            }


            $materials = $query->paginate(10);
            return response()->json($materials);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to fetch materials',
                'message' => $e->getMessage()
            ], 500);
        }
    }
    
    public function store(Request $request)
    {
            // dd($request->all());
        try{
            $request->validate([
                'name' => [
                    'required',
                    'string',
                    'max:255',
                    Rule::unique('materials', 'name')->whereNull('deleted_at'),
                ],
            ]);

            $material = new Material();
            $material->name = $request->name;
            $material->unit_of_measurement_id = $request->unit_of_measurement_id;
            $material->size = $request->size ;
            $material->hsn_code = $request->hsn_code ;
            $material->price = $request->price;
            $material->remark = $request->remark;
            $material->category_id = $request->category_id;
            $material->group_id = $request->group_id?? null;
            if($request->has('image')){
                $image = $request->file('image');
                $randomName = rand(10000000, 99999999);
                $imageName = time().'_'.$randomName . '.' . $image->getClientOriginalExtension();
                $image->move(public_path('uploads/materials/'), $imageName);
                $material->image = '/uploads/materials/'.$imageName;

            }
            $material->opening_stock = $request->opening_stock;
            $material->urgently_required = $request->urgently_required;
            $material->minimum_qty = $request->minimum_qty;
            $material->tag = $request->tag;
            $material->created_by = auth()->user()->id;
            $material->status = $request->status ?? 1;
            $material->save();
            $material->load('category', 'group','unitOfMeasurement');
            return response()->json(['message' => 'Material created successfully',
                'data' => $material]);
        }catch(\Exception $e){
            return response()->json(['error' => 'Failed to store material', $e->getMessage()], 500);
        }
        
    }

    public function edit(Request $request, $id)
    {
        try{

            $material =Material::find($id);

            if(!$material){
                return response()->json(['error' => 'Material not found'], 404);
            }
            return response()->json(['message' => 'Material fetch  successfully',
                'data' => $material]);
        }catch(\Exception $e){
            return response()->json(['error' => 'Failed to fetch material', $e->getMessage()], 500);
        }
        
    }

    public function update(Request $request, $id){
        try{

            $request->validate([
                'name' => [
                    'required',
                    'string',
                    'max:255',
                    Rule::unique('materials', 'name')->ignore($id)->whereNull('deleted_at'),
                ],
            ]);

            $material =Material::find($id);

            if(!$material){
                return response()->json(['error' => 'Material not found'], 404);
            }

            $material->name = $request->name;
            $material->unit_of_measurement_id = $request->unit_of_measurement_id;
            $material->size = $request->size ;
            $material->hsn_code = $request->hsn_code ;
            $material->price = $request->price;
            $material->remark = $request->remark;
            $material->category_id = $request->category_id ?? $material->category_id;
            $material->group_id = $request->group_id?? null;;
            if ($request->has('image') && $request->file('image')) {
                $image = $request->file('image');
                $randomName = rand(10000000, 99999999);
                $imageName = time().'_'.$randomName . '.' . $image->getClientOriginalExtension();
                $image->move(public_path('uploads/materials/'), $imageName);
                $material->image = '/uploads/materials/'.$imageName;

            }
            $material->opening_stock = $request->opening_stock;
            $material->urgently_required = $request->urgently_required;
            $material->minimum_qty = $request->minimum_qty;
            $material->tag = $request->tag;
            $material->created_by = auth()->user()->id;
            $material->status = $request->status ?? $material->status;
            $material->save();
            $material->load('category', 'group','unitOfMeasurement');

            return response()->json(['message' => 'Material updated  successfully',
                'data' => $material]);
        }catch(\Exception $e){
            return response()->json(['error' => 'Failed to update material', $e->getMessage()], 500);
        }
        
    }

    public function delete(Request $request, $id){
        try{

            $material =Material::find($id);

            if(!$material){
                return response()->json(['error' => 'Material not found'], 404);
            }
            $material->delete();

            return response()->json(['message' => 'Material deleted  successfully']);
        }catch(\Exception $e){
            return response()->json(['error' => 'Failed to delete material', $e->getMessage()], 500);
        }
        
    }

    public function statusUpdate(Request $request)
    {
        try{
            $id = $request->id;
            $material =Material::find($id);

            if(!$material){
                return response()->json(['error' => 'Material not found'], 404);
            }
            $material->status= !$material->status;
            $material->save();

            return response()->json(['message' => 'Material status updated  successfully']);
        }catch(\Exception $e){
            return response()->json(['error' => 'Failed to fetch  material', $e->getMessage()], 500);
        }
        
    }
    
    // public function meterialLogs(Request $request)
    // {
    //     try{
            
    //         $query = MaterialLog::orderBy();
            
    //     } catch(\Exception $e){
    //         return response()->json(['error' => 'Failed to fetch  material logs', $e->getMessage()], 500);
    //     }
    // }
    
  public function materialLogs(Request $request)
    {
        try {
            $validated = $request->validate([
                'per_page'     => 'nullable|integer|min:1|max:100',
                'page'         => 'nullable|integer|min:1',
                'material_id'  => 'nullable|integer|exists:materials,id',
                'start_date'   => 'nullable|date',
                'end_date'     => 'nullable|date|after_or_equal:start_date',
            ]);
    
            $perPage = $validated['per_page'] ?? 10;
    
            // check if date range is provided for aggregated view
            $hasDateRange = !empty($validated['start_date']) && !empty($validated['end_date']);
    
            if ($hasDateRange) {
                // Return aggregated summary by material
                return $this->getMaterialSummary($validated);
            }
    
            // Default: Return transaction logs
            $query = DB::table('material_logs as ml')
                ->leftJoin('materials as m', 'ml.material_id', '=', 'm.id')
                ->select(
                    'ml.id',
                    'ml.material_id',
                    'm.name as material_name',
                    'ml.type',
                    'ml.qty',
                    'ml.previous_qty',
                    'ml.new_qty',
                    'ml.reference_type',
                    'ml.reference_id',
                    'ml.action_by',
                    'ml.remarks',
                    'ml.created_at',
                    'ml.updated_at'
                )
                ->orderByDesc('ml.id');
    
            if (!empty($validated['material_id'])) {
                $query->where('ml.material_id', $validated['material_id']);
            }
    
            if (!empty($validated['start_date'])) {
                $query->where('ml.created_at', '>=', $validated['start_date'] . ' 00:00:00');
            }
    
            if (!empty($validated['end_date'])) {
                $query->where('ml.created_at', '<=', $validated['end_date'] . ' 23:59:59');
            }
    
            $items = $query->paginate($perPage);
    
            return response()->json([
                'success'       => true,
                'data'          => $items->items(),
                'current_page'  => $items->currentPage(),
                'last_page'     => $items->lastPage(),
                'per_page'      => $items->perPage(),
                'total'         => $items->total(),
                'from'          => $items->firstItem(),
                'to'            => $items->lastItem(),
                'view_type'     => 'logs',
            ], 200);
    
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors'  => $e->errors(),
            ], 422);
    
        } catch (\Exception $e) {
            \Log::error('Material Logs error', [
                'message' => $e->getMessage(),
                'trace'   => $e->getTraceAsString(),
            ]);
    
            return response()->json([
                'success' => false,
                'message' => config('app.debug') ? $e->getMessage() : 'Something went wrong',
            ], 500);
        }
    }
    
    private function getMaterialSummary($validated)
    {
        $startDate = $validated['start_date'] . ' 00:00:00';
        $endDate = $validated['end_date'] . ' 23:59:59';
    
        // Get all materials or filtered material
        $materialsQuery = DB::table('materials')
            ->where('status', 1)
            ->orderBy('name');
    
        if (!empty($validated['material_id'])) {
            $materialsQuery->where('id', $validated['material_id']);
        }
    
        $materials = $materialsQuery->get();
    
        $summaryData = [];
    
        foreach ($materials as $material) {
            // Get opening stock (new_qty from the last log before start_date)
            $openingStock = DB::table('material_logs')
                ->where('material_id', $material->id)
                ->where('created_at', '<', $startDate)
                ->orderByDesc('created_at')
                ->value('new_qty') ?? 0;
    
            // Get closing stock (new_qty from the last log before or at end_date)
            $closingStock = DB::table('material_logs')
                ->where('material_id', $material->id)
                ->where('created_at', '<=', $endDate)
                ->orderByDesc('created_at')
                ->value('new_qty') ?? $openingStock;
    
            // Sum of IN transactions in the date range
            $totalIn = DB::table('material_logs')
                ->where('material_id', $material->id)
                ->where('type', 'IN')
                ->whereBetween('created_at', [$startDate, $endDate])
                ->sum('qty') ?? 0;
    
            // Sum of OUT transactions in the date range
            $totalOut = DB::table('material_logs')
                ->where('material_id', $material->id)
                ->where('type', 'OUT')
                ->whereBetween('created_at', [$startDate, $endDate])
                ->sum('qty') ?? 0;
    
            // Include ALL materials when date range is provided
            // This shows complete inventory report including materials with zero transactions
            $summaryData[] = [
                'id' => $material->id,
                'material_id' => $material->id,
                'material_name' => $material->name,
                'opening_stock' => (float) $openingStock,
                'total_in' => (float) $totalIn,
                'total_out' => (float) $totalOut,
                'closing_stock' => (float) $closingStock,
            ];
        }
    
        // Manual pagination for aggregated data
        $perPage = $validated['per_page'] ?? 10;
        $page = $validated['page'] ?? 1;
        $total = count($summaryData);
        $offset = ($page - 1) * $perPage;
        $paginatedData = array_slice($summaryData, $offset, $perPage);
    
        return response()->json([
            'success'       => true,
            'data'          => $paginatedData,
            'current_page'  => (int) $page,
            'last_page'     => (int) ceil($total / $perPage),
            'per_page'      => (int) $perPage,
            'total'         => $total,
            'from'          => $offset + 1,
            'to'            => min($offset + $perPage, $total),
            'view_type'     => 'summary',
        ], 200);
    }
}

