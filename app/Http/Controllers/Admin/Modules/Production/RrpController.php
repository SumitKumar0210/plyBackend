<?php
namespace App\Http\Controllers\Admin\Modules\Production;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Rrp;
use App\Models\ProductionOrder;
use App\Models\ProductionProduct;
use App\Models\EmployeeWorksheet;
use App\Models\MaterialRequest;
use App\Models\Labour;
use App\Models\Material;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Carbon\Carbon;

class RrpController extends Controller
{
   
    public function store(Request $request)
    {
        try {
            $request->validate([
                'id' => 'required|exists:production_products,id',
                'miscellaneous_cost' => 'required|numeric|min:0',
                'gross_profit' => 'required|numeric|min:0|max:100',
            ]);

            $ppId = $request->id;
            $miscellaneousCost = $request->miscellaneous_cost;
            $grossProfitPercentage = $request->gross_profit;

           
            $pp = ProductionProduct::find($ppId);
            
            if (!$pp) {
                return response()->json([
                    'success' => false,
                    'message' => 'Production product not found'
                ], 404);
            }
            
             

            // Calculate RRP
            $rrpData = $this->calculateRrp($ppId, $miscellaneousCost, $grossProfitPercentage);

            if (!$rrpData['success']) {
                return response()->json([
                    'success' => false,
                    'message' => $rrpData['message']
                ], 400);
            }

           
            $rrp = Rrp::updateOrCreate(
                ['pp_id' => $ppId],
                [
                    'material_cost' => $rrpData['material_cost'],
                    'labour_cost' => $rrpData['labour_cost'],
                    'gross_profit' => $grossProfitPercentage,
                    'gross_profit_amount' => $rrpData['gross_profit_amount'],
                    'miscellaneous' => $miscellaneousCost,
                    'unit_cost' => $rrpData['unit_cost'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );

           
            $pp->rrp_price = $rrpData['unit_cost'];
            $pp->save();

            return response()->json([
                'success' => true,
                'message' => 'RRP calculated and saved successfully',
                'data' => [
                    'rrp' => $rrp,
                    'breakdown' => $rrpData
                ]
            ], 200);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while calculating RRP',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    
    public function calculateRrp($ppId, $miscellaneousCost, $grossProfitPercentage)
    {
        try {
            // Get Production Product
            $pp = ProductionProduct::find($ppId);
            
            if (!$pp) {
                return [
                    'success' => false,
                    'message' => 'Production product not found'
                ];
            }

            $productionQty = $pp->qty ?? 1; 

            $materialCost = $this->calculateMaterialCost($ppId);

            $labourdata = $this->calculateLabourCost($ppId);
            $labourCost = $labourdata['regularCost'] + $labourdata['overtimeCost'];

            $totalCost = $materialCost + $labourCost + $miscellaneousCost;

            $grossProfitAmount = ($totalCost * $grossProfitPercentage) / 100;

            $unitCost = ($totalCost + $grossProfitAmount) / $productionQty;

            return [
                'success' => true,
                'material_cost' => round($materialCost, 2),
                'labour_cost' => round($labourCost, 2),
                'miscellaneous_cost' => round($miscellaneousCost, 2),
                'total_cost' => round($totalCost, 2),
                'gross_profit_percentage' => $grossProfitPercentage,
                'gross_profit_amount' => round($grossProfitAmount, 2),
                'unit_cost' => round($unitCost, 2),
                'production_qty' => $productionQty,
                'labourdata' => $labourdata,
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Error calculating RRP: ' . $e->getMessage()
            ];
        }
    }

    private function calculateMaterialCost($ppId)
    {
        $materialRequests = MaterialRequest::with('material')
            ->where('pp_id', $ppId)
            ->get();

        $totalMaterialCost = 0;

        foreach ($materialRequests as $request) {
            if ($request->material) {
                $materialPrice = $request->material->price ?? 0;
                $quantity = $request->qty ?? 0;
                $totalMaterialCost += ($materialPrice * $quantity);
            }
        }

        return $totalMaterialCost;
    }

    private function calculateLabourCost($ppId)
    {
        try{
        $employeeWorksheets = EmployeeWorksheet::with('labour', 'labour.shift')
            ->where('pp_id', $ppId)
            ->get();
        
        $totalLabourCost = 0;
        
        foreach ($employeeWorksheets as $worksheet) {
            if ($worksheet->labour) {
                
                // $totalMinutes = $worksheet->total_minutes ?? 0;
                
              
                $overtimeMinutes = $worksheet->overtime ?? 0;
                
                
                
               $start = Carbon::createFromFormat('H:i', $worksheet->labour->shift->start_time);
                $end   = Carbon::createFromFormat('H:i', $worksheet->labour->shift->end_time);
                
                // Handle night shift (end time next day)
                if ($end->lessThanOrEqualTo($start)) {
                    $end->addDay();
                }
                
                // total minutes
                $totalMinutes = $start->diffInMinutes($end);
                

                $breakMinutes =  0;
                $regularMinutes = max(0, $totalMinutes - $breakMinutes);
                
                // convert to hours
                $regularHours = round($regularMinutes / 60, 2);
                $overtimeHours = $overtimeMinutes / 60;
                
              
              
                $hourlyRate = $worksheet->labour->per_hour_cost ?? 0;
                
               
               
                // $regularCost = 1000;
                $regularCost = $regularHours * $hourlyRate;
                
               
                $overtimeHourlyRate = $worksheet->labour->overtime_hourly_rate ?? 0; 
                
                $overtimeCost = $overtimeHours * $overtimeHourlyRate;
                
                // Total cost for this worksheet
                $worksheetCost = $regularCost + $overtimeCost;
                
                $totalLabourCost += $worksheetCost;
            }
        }
        
        // return $totalLabourCost;
        return $data=['overtimeCost' =>$overtimeCost,
        'regularCost' =>$regularCost,
        'start' =>$start,
        'end' =>$end,
        'regularHours' =>$regularHours];
        } catch (\Exception $e) {
            \Log::error($e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function getRrp($ppId)
    {
        try {
            $rrp = Rrp::where('pp_id', $ppId)->first();

            if (!$rrp) {
                return response()->json([
                    'success' => false,
                    'message' => 'RRP not found for this product'
                ], 404);
            }

            $pp = ProductionProduct::find($ppId);

            return response()->json([
                'success' => true,
                'data' => [
                    'rrp' => $rrp,
                    'product' => $pp,
                    'breakdown' => [
                        'material_cost' => $rrp->material_cost,
                        'labour_cost' => $rrp->labour_cost,
                        'miscellaneous_cost' => $rrp->miscellaneous_cost,
                        'gross_profit' => $rrp->gross_profit,
                        'unit_cost' => $rrp->unit_cost,
                    ]
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching RRP: ' . $e->getMessage()
            ], 500);
        }
    }

    public function previewRrp(Request $request)
    {
        try {
            $request->validate([
                'id' => 'required|exists:production_products,id',
                'miscellaneous_cost' => 'required|numeric|min:0',
                'gross_profit' => 'required|numeric|min:0|max:100',
            ]);

            $ppId = $request->id;
            $miscellaneousCost = $request->miscellaneous_cost;
            $grossProfitPercentage = $request->gross_profit;

            // Calculate RRP
            $rrpData = $this->calculateRrp($ppId, $miscellaneousCost, $grossProfitPercentage);

            if (!$rrpData['success']) {
                return response()->json([
                    'success' => false,
                    'message' => $rrpData['message']
                ], 400);
            }

            return response()->json([
                'success' => true,
                'message' => 'RRP calculated successfully',
                'data' => $rrpData
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error calculating RRP: ' . $e->getMessage()
            ], 500);
        }
    }

    
    public function getCostBreakdown($ppId)
    {
        try {
            $pp = ProductionProduct::find($ppId);

            if (!$pp) {
                return response()->json([
                    'success' => false,
                    'message' => 'Production product not found'
                ], 404);
            }

            // Get material breakdown
            $materials = MaterialRequest::with('material')
                ->where('pp_id', $ppId)
                ->get()
                ->map(function ($request) {
                    $price = $request->material->price ?? 0;
                    $qty = $request->qty ?? 0;
                    $total = $price * $qty;
                    
                    return [
                        'material_id' => $request->material_id,
                        'material_name' => $request->material->name ?? 'N/A',
                        'size' => $request->size ?? 'N/A',
                        'qty' => $qty,
                        'price_per_unit' => $price,
                        'total_cost' => round($total, 2)
                    ];
                });

            // Get labour breakdown
            $labours = EmployeeWorksheet::with('labour')
                ->where('pp_id', $ppId)
                ->get()
                ->map(function ($worksheet) {
                    $totalMinutes = $worksheet->total_minutes ?? 0;
                    $totalHours = $totalMinutes / 60;
                    $hourlyRate = $worksheet->labour->per_hour_cost ?? 0;
                    $cost = $totalHours * $hourlyRate;
                    
                    return [
                        'labour_id' => $worksheet->labour_id,
                        'labour_name' => $worksheet->labour->name ?? 'N/A',
                        'date' => $worksheet->date,
                        'sign_in' => $worksheet->sign_in,
                        'sign_out' => $worksheet->sign_out,
                        'total_minutes' => $totalMinutes,
                        'total_hours' => round($totalHours, 2),
                        'hourly_rate' => $hourlyRate,
                        'total_cost' => round($cost, 2)
                    ];
                });

            $totalMaterialCost = $materials->sum('total_cost');
            $totalLabourCost = $labours->sum('total_cost');

            return response()->json([
                'success' => true,
                'data' => [
                    'product' => [
                        'id' => $pp->id,
                        'name' => $pp->item_name,
                        'qty' => $pp->qty,
                    ],
                    'materials' => $materials,
                    'labours' => $labours,
                    'summary' => [
                        'total_material_cost' => round($totalMaterialCost, 2),
                        'total_labour_cost' => round($totalLabourCost, 2),
                        'total_cost' => round($totalMaterialCost + $totalLabourCost, 2)
                    ]
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching cost breakdown: ' . $e->getMessage()
            ], 500);
        }
    }
}