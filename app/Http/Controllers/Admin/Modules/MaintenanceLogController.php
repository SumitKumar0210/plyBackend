<?php

namespace App\Http\Controllers\Admin\modules;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\MaintenanceLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class MaintenanceLogController extends Controller
{
    public function getData(Request $request)
    {
        try{
            $logs = MaintenanceLog::with('user:id,name','machine')->orderBy('id','desc')->paginate(10);
            return response()->json($logs);
        }catch(\Exception $e){
            return response()->json(['error' => 'Failed to fetch maintenance logs'.$e->getMessage()], 500);
        }
        
    }

    public function search(Request $request)
    {
        try{
            $query = MaintenanceLog::with(['user:id,name', 'machine'])->orderBy('id', 'desc');

            if ($request->filled('user')) {
                $query->whereHas('user', function ($q) use ($request) {
                    $q->where('name', 'ILIKE', '%' . $request->user . '%');
                });
            }

            if ($request->filled('machien')) {
                $query->whereHas('machien', function ($q) use ($request) {
                    $q->where('name', 'ILIKE', '%' . $request->machien . '%');
                });
            }

            if ($request->filled('remark')) {
                $query->where('remark', 'ILIKE', '%' . $request->remark . '%');  
            }

            if ($request->has('status') && $request->status !== null) {
                $query->where('status', $request->status);
            }

            $logs = $query->paginate(10);
            return response()->json($logs);
        }catch(\Exception $e){
            return response()->json(['error' => 'Failed to fetch maintenance logs'], 500);
        }
    }

    public function store(Request $request)
    {
        try{
            
            $request->validate([
                'machine_id' => 'required|integer|exists:machines,id',
                'user_id' => 'required|integer|exists:users,id',
                'remark' => 'nullable|string|max:255',
            ]);

            $log = new MaintenanceLog();

            $log->machine_id = $request->machine_id;
            $log->user_id = $request->user_id;
            $log->remark = $request->remark;
            $log->status = $request->status ?? 0;
            $log->save();
            return response()->json(['message' => 'Maintenance log created successfully',
                'data' => $log]);
        }catch(\Exception $e){
            return response()->json(['error' => 'Failed to store maintenance log', $e->getMessage()], 500);
        }
        
    }

    public function edit(Request $request, $id)
    {
        try{
            $log =MaintenanceLog::find($id);

            if(!$log){
                return response()->json(['error' => 'Maintenance log not found'], 404);
            }
            return response()->json(['message' => 'Maintenance log fetch  successfully',
                'data' => $log]);
        }catch(\Exception $e){
            return response()->json(['error' => 'Failed to fetch maintenance log', $e->getMessage()], 500);
        }
        
    }

    public function update(Request $request, $id)
    {
        try{
            
            $request->validate([
                'machine_id' => 'required|integer|exists:machines,id',
                'user_id' => 'required|integer|exists:users,id',
                'remark' => 'nullable|string|max:255',
                'status' => 'nullable|in:0,1',
            ]);

            $log = MaintenanceLog::find($id);

            if(!$log){
                return response()->json(['error' => 'Maintenance log not found'], 404);
            }
            $log->machine_id = $request->machine_id;
            $log->user_id = $request->user_id;
            $log->remark = $request->remark;
            $log->status = $request->status ?? $log->status;
            $log->save();

            return response()->json(['message' => 'Maintenance log updated  successfully',
                'data' => $log]);
        }catch(\Exception $e){
            return response()->json(['error' => 'Failed to fetch maintenance log', $e->getMessage()], 500);
        }
        
    }

    public function delete(Request $request, $id){
        try{
            $log = MaintenanceLog::find($id);

            if(!$log){
                return response()->json(['error' => 'Maintenance log not found'], 404);
            }

            $log->delete();
            return response()->json(['message' => 'Maintenance log deleted  successfully']);
        }catch(\Exception $e){
            return response()->json(['error' => 'Failed to fetch maintenance log', $e->getMessage()], 500);
        }
        
    }

    public function statusUpdate(Request $request)
    {
        try{
            $id = $request->id;
            $log = MaintenanceLog::find($id);

            if(!$log){
                return response()->json(['error' => 'Maintenance log not found'], 404);
            }
            $log->status= !$log->status;
            $log->save();

            return response()->json(['message' => 'Maintenance log status updated  successfully']);
        }catch(\Exception $e){
            return response()->json(['error' => 'Failed to fetch  maintenance log', $e->getMessage()], 500);
        }
        
    }
}
