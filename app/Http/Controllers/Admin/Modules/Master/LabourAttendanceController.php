<?php

namespace App\Http\Controllers\Admin\Modules\Master;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Labour;
use App\Models\LabourAttendance;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class LabourAttendanceController extends Controller
{
    public function getData(Request $request)
    {
        try {
            $query = LabourAttendance::query();
    
            // Filter by exact date
            if ($request->date) {
                $query->whereDate('date', $request->date);
            }
    
            // Filter by month & year (calendar view)
            if ($request->month && $request->year) {
                $query->whereMonth('date', $request->month)
                      ->whereYear('date', $request->year);
            }
    
            $records = $query
                ->orderBy('date', 'asc')
                ->get();
    
           
            $groupedData = $records->groupBy(function ($item) {
                return Carbon::parse($item->date)->format('Y-m-d');
            });
    
            return response()->json([
                'success' => true,
                'data' => $groupedData,
            ], 200);
    
        } catch (\Exception $e) {
    
            Log::error('LabourAttendance getData error', [
                'message' => $e->getMessage(),
            ]);
    
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch labour attendance',
            ], 500);
        }
    }
    
   public function markAttendance(Request $request)
    {
        
        $validator = Validator::make($request->all(), [
            'attendance' => 'required|array',
    
            'attendance.*.labour_id' => 'required|integer|exists:labours,id',
            'attendance.*.attendance_date' => 'required|date',
    
            'attendance.*.sign_in_time' => 'nullable|date_format:H:i:s',
            'attendance.*.sign_out_time' => 'nullable|date_format:H:i:s',
        ]);
    
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
                'errors'  => $validator->errors(),
            ], 422);
        }
    
        try {
            DB::beginTransaction();
    
            $saved = [];
    
            foreach ($request->input('attendance') as $row) {
    
                $attendance = LabourAttendance::updateOrCreate(
                    [
                        'labour_id' => $row['labour_id'],
                        'date' => $row['attendance_date'],
                    ],
                    [
                        'sign_in'  => $row['sign_in_time'] ?? null,
                        'sign_out' => $row['sign_out_time'] ?? null,
                    ]
                );
    
                $saved[] = $attendance;
            }
    
            DB::commit();
    
            return response()->json([
                'success' => true,
                'message' => 'Attendance saved successfully',
                'data'    => $saved,
            ], 200);
    
        } catch (\Exception $e) {
    
            DB::rollBack();
    
            Log::error('LabourAttendance markAttendance error', [
                'message' => $e->getMessage(),
            ]);
    
            return response()->json([
                'success' => false,
                'message' => 'Failed to save attendance',
            ], 500);
        }
    }

}
