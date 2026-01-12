<?php

namespace App\Http\Controllers\Admin\modules;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ProductionLog;
use App\Models\InwardPaymentLog;
use App\Models\CustomerPaymentLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Carbon\Carbon;

class LogController extends Controller
{
    
    public function getProductionLog(Request $request)
    {
        try {
            $query = ProductionLog::with('fromStage:id,name','toStage:id,name','user:id,name','order','productionProduct');

            // Search
            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('id', 'like', "%{$search}%")
                      ->orWhere('po_id', 'like', "%{$search}%")
                      ->orWhere('production_product_id', 'like', "%{$search}%")
                      ->orWhere('from_stage', 'like', "%{$search}%")
                      ->orWhere('to_stage', 'like', "%{$search}%")
                      ->orWhere('action_by', 'like', "%{$search}%")
                      ->orWhere('remark', 'like', "%{$search}%")
                      // Search in order relationship
                      ->orWhereHas('order', function($subQ) use ($search) {
                          $subQ->where('batch_no', 'like', "%{$search}%");
                      })
                      // Search in production product relationship
                      ->orWhereHas('productionProduct', function($subQ) use ($search) {
                          $subQ->where('item_name', 'like', "%{$search}%");
                      })
                      // Search in user relationship
                      ->orWhereHas('user', function($subQ) use ($search) {
                          $subQ->where('name', 'like', "%{$search}%");
                      });
                });
            }

            // Status filter
            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }

            // From stage filter
            if ($request->filled('from_stage')) {
                $query->where('from_stage', $request->from_stage);
            }

            // To stage filter
            if ($request->filled('to_stage')) {
                $query->where('to_stage', $request->to_stage);
            }

            // Date range filter
            if ($request->filled('start_date')) {
                $query->whereDate('created_at', '>=', $request->start_date);
            }

            if ($request->filled('end_date')) {
                $query->whereDate('created_at', '<=', $request->end_date);
            }

            // Get pagination parameters
            $perPage = $request->get('per_page', 10);
            
            // Get paginated data
            $data = $query->orderBy('id', 'desc')->paginate($perPage);

            return response()->json([
                'success' => true,
                'message' => 'Production logs fetched successfully',
                'data' => $data->items(),
                'total' => $data->total(),
                'current_page' => $data->currentPage(),
                'per_page' => $data->perPage(),
                'last_page' => $data->lastPage(),
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch production logs',
                'error' => $e->getMessage()
            ], 500);
        }
    }

   
    public function getVendorPaymentLog(Request $request)
    {
        try {
            $query = InwardPaymentLog::with('inward','inward.vendor');

            // Search
            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('id', 'like', "%{$search}%")
                      ->orWhere('inward_id', 'like', "%{$search}%")
                      ->orWhere('payment_mode', 'like', "%{$search}%")
                      ->orWhere('reference_no', 'like', "%{$search}%")
                      ->orWhere('added_by', 'like', "%{$search}%")
                      // Search in inward -> vendor relationship
                      ->orWhereHas('inward.vendor', function($subQ) use ($search) {
                          $subQ->where('name', 'like', "%{$search}%")
                               ->orWhere('mobile', 'like', "%{$search}%");
                      })
                      // Search in inward relationship
                      ->orWhereHas('inward', function($subQ) use ($search) {
                          $subQ->where('purchase_order_id', 'like', "%{$search}%");
                      });
                });
            }

            // Payment mode filter
            if ($request->filled('payment_mode')) {
                $query->where('payment_mode', $request->payment_mode);
            }

            // Status filter
            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }

            // Purchase order filter
            if ($request->filled('purchase_order_id')) {
                $query->whereHas('inward', function($q) use ($request) {
                    $q->where('purchase_order_id', $request->purchase_order_id);
                });
            }

            // Date range filter
            if ($request->filled('start_date')) {
                $query->whereDate('date', '>=', $request->start_date);
            }

            if ($request->filled('end_date')) {
                $query->whereDate('date', '<=', $request->end_date);
            }

            // Get pagination parameters
            $perPage = $request->get('per_page', 10);
            
            // Get paginated data
            $data = $query->orderBy('id', 'desc')->paginate($perPage);

            return response()->json([
                'success' => true,
                'message' => 'Vendor payment logs fetched successfully',
                'data' => $data->items(),
                'total' => $data->total(),
                'current_page' => $data->currentPage(),
                'per_page' => $data->perPage(),
                'last_page' => $data->lastPage(),
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch vendor payment logs',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getCustomerPaymentLog(Request $request)
    {
        try {
            $query = CustomerPaymentLog::with('bill.customer');

            // Search
            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('id', 'like', "%{$search}%")
                      ->orWhere('bill_id', 'like', "%{$search}%")
                      ->orWhere('payment_mode', 'like', "%{$search}%")
                      ->orWhere('reference_no', 'like', "%{$search}%")
                      ->orWhere('added_by', 'like', "%{$search}%")
                     
                      // Search customer name via bill → customer
                      ->orWhereHas('bill.customer', function ($subQ) use ($search) {
                          $subQ->where('name', 'like', "%{$search}%");
                      })
            
                      // Search bill fields
                      ->orWhereHas('bill', function ($subQ) use ($search) {
                          $subQ->where('invoice_no', 'like', "%{$search}%");
                      });
                });
            }

            // Payment mode filter
            if ($request->filled('payment_mode')) {
                $query->where('payment_mode', $request->payment_mode);
            }

            // Date range filter
            if ($request->filled('start_date')) {
                $query->whereDate('date', '>=', $request->start_date);
            }

            if ($request->filled('end_date')) {
                $query->whereDate('date', '<=', $request->end_date);
            }

            // Get pagination parameters
            $perPage = $request->get('per_page', 10);
            
            // Get paginated data
            $data = $query->orderBy('id', 'desc')->paginate($perPage);

            return response()->json([
                'success' => true,
                'message' => 'Customer payment logs fetched successfully',
                'data' => $data->items(),
                'total' => $data->total(),
                'current_page' => $data->currentPage(),
                'per_page' => $data->perPage(),
                'last_page' => $data->lastPage(),
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch customer payment logs',
                'error' => $e->getMessage()
            ], 500);
        }
    }

}