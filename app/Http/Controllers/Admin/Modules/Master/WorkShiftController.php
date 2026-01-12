<?php

namespace App\Http\Controllers\Admin\Modules\Master;

use App\Http\Controllers\Controller;
use App\Models\WorkShift;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
class WorkShiftController extends Controller
{
    
    public function getData(Request $request)
    {
        try {
    
            $query = WorkShift::query();
    
            if ($request->has('status')) {
                $query->where('status', '1');
            }
    
            $workShifts = $query
                ->orderBy('created_at', 'desc')
                ->get();
    
            return response()->json([
                'success' => true,
                'data' => $workShifts,
                'message' => 'Work shifts fetched successfully'
            ], 200);
    
        } catch (\Exception $e) {
    
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch work shifts',
                'error' => $e->getMessage()
            ], 500);
        }
    }


    
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|min:2|max:50|unique:work_shifts,name',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i',
            'break_minutes' => 'required|integer|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            // $workShift = WorkShift::create([
            //     'name' => $request->name,
            //     'start_time' => $request->start_time,
            //     'end_time' => $request->end_time,
            //     'break_minutes' => intval($request->break_minutes),
            //     'status' => 1,
            // ]);
            $workShift = WorkShift::create([
                'name'           => $request->name,
                'start_time'     => Carbon::createFromFormat('H:i', $request->start_time)->format('H:i:s'),
                'end_time'       => Carbon::createFromFormat('H:i', $request->end_time)->format('H:i:s'),
                'break_minutes'  => (int) $request->break_minutes,
                'is_night_shift' => Carbon::createFromFormat('H:i', $request->end_time)
                                        ->lessThan(Carbon::createFromFormat('H:i', $request->start_time)),
                'status'         => 1,
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => $workShift,
                'message' => 'Work shift created successfully'
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to create work shift',
                'error' => $e->getMessage()
            ], 500);
        }
    }

   
    public function edit($id)
    {
        try {
            $workShift = WorkShift::findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $workShift,
                'message' => 'Work shift fetched successfully'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Work shift not found',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $workShift = WorkShift::findOrFail($id);

            $validator = Validator::make($request->all(), [
                'name' => 'required|string|min:2|max:50|unique:work_shifts,name,' . $id,
                'start_time' => 'required|date_format:H:i',
                'end_time' => 'required|date_format:H:i',
                'break_minutes' => 'required|integer|min:0',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            DB::beginTransaction();

            $workShift->update([
                'name' => $request->name,
                'start_time' => $request->start_time,
                'end_time' => $request->end_time,
                'break_minutes' => $request->break_minutes,
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => $workShift->fresh(),
                'message' => 'Work shift updated successfully'
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to update work shift',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function statusUpdate(Request $request)
    {
        

        try {
            $workShift = WorkShift::find($request->id);
            $workShift->status = !$workShift->status;
            $workShift->save();

            return response()->json([
                'success' => true,
                'data' => $workShift->fresh(),
                'message' => 'Status updated successfully'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update status',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function delete($id)
    {
        try {
            $workShift = WorkShift::findOrFail($id);
            
            DB::beginTransaction();
            
            $workShift->delete();
            
            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Work shift deleted successfully'
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete work shift',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getActive()
    {
        try {
            $workShifts = WorkShift::active()->orderBy('start_time')->get();

            return response()->json([
                'success' => true,
                'data' => $workShifts,
                'message' => 'Active work shifts fetched successfully'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch active work shifts',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // public function getNightShifts()
    // {
    //     try {
    //         $nightShifts = WorkShift::nightShift()->active()->get();

    //         return response()->json([
    //             'success' => true,
    //             'data' => $nightShifts,
    //             'message' => 'Night shifts fetched successfully'
    //         ], 200);
    //     } catch (\Exception $e) {
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Failed to fetch night shifts',
    //             'error' => $e->getMessage()
    //         ], 500);
    //     }
    // }

    // public function getDayShifts()
    // {
    //     try {
    //         $dayShifts = WorkShift::dayShift()->active()->get();

    //         return response()->json([
    //             'success' => true,
    //             'data' => $dayShifts,
    //             'message' => 'Day shifts fetched successfully'
    //         ], 200);
    //     } catch (\Exception $e) {
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Failed to fetch day shifts',
    //             'error' => $e->getMessage()
    //         ], 500);
    //     }
    // }
}