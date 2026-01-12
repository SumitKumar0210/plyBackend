<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\Models\User;
use App\Models\Material;
use App\Models\Product;
use App\Models\PurchaseInwardLog;
use App\Mail\LowInventoryAlertMail;
use App\Mail\PurchasePeriodAlertMail;
use App\Mail\LowProductInventoryAlertMail;
use App\Mail\DailyAttendanceReportMail;
use App\Mail\PurchaseOrderMail;
use App\Mail\QuotationMail;
use App\Mail\ChallanMail;
use Carbon\Carbon;
use App\Models\Labour;
use App\Models\LabourAttendance;
use App\Models\GeneralSetting;
use App\Models\Customer;
use App\Models\Quotation;
use App\Models\Billing;
use App\Models\PublicLink;
use App\Models\Vendor;
use App\Models\PurchaseOrder;

class MailController extends Controller
{
    /**
     * Send low inventory alert emails for materials
     */
    public function alertLowInventory()
    {
        try {
            // Get all admin users
            $adminUsers = User::role('admin')->get();
            $admin = User::role('admin')->where('email','sumitkrtechie@gmail.com')->first();

            if ($adminUsers->isEmpty()) {
                Log::warning('No admin users found for low inventory alert');
                return response()->json([
                    'success' => false,
                    'message' => 'No admin users found'
                ], 404);
            }

            // Get low inventory materials
            $lowInventoryMaterials = Material::where('urgently_required', 1)
                ->whereColumn('available_qty', '<=', 'minimum_qty')
                ->get();

            if ($lowInventoryMaterials->isEmpty()) {
                Log::info('No low inventory materials found');
                return response()->json([
                    'success' => true,
                    'message' => 'No low inventory materials found'
                ], 200);
            }

            Log::info('Low inventory materials found', [
                'count' => $lowInventoryMaterials->count(),
                'materials' => $lowInventoryMaterials->pluck('name')
            ]);

            // Send email to each admin
            $sentCount = 0;
            $errors = [];
            //  Mail::to($admin->email)->send(new LowInventoryAlertMail($admin, $lowInventoryMaterials));
            
            foreach ($adminUsers as $admin) {
                try {
                    Mail::to($admin->email)->send(new LowInventoryAlertMail($admin, $lowInventoryMaterials));
                    $sentCount++;
                    Log::info("Low inventory alert sent to {$admin->email}");
                } catch (\Exception $e) {
                    $errors[] = $admin->email;
                    Log::error('Failed to send low inventory alert', [
                        'email' => $admin->email,
                        'admin_id' => $admin->id,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ]);
                }
            }

            return response()->json([
                'success' => true,
                'message' => "Low inventory alerts sent successfully to {$sentCount} admin(s)",
                'materials_count' => $lowInventoryMaterials->count(),
                'admins_notified' => $sentCount,
                'failed_emails' => $errors
            ], 200);

        } catch (\Exception $e) {
            Log::error('Low inventory alert failed completely', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to send low inventory alerts',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Send low inventory alert emails for products
     */
    public function alertLowProductInventory()
    {
        try {
            // Get all admin users
            $adminUsers = User::role('admin')->get();

            if ($adminUsers->isEmpty()) {
                Log::warning('No admin users found for low product inventory alert');
                return response()->json([
                    'success' => false,
                    'message' => 'No admin users found'
                ], 404);
            }

            // Get low inventory products
            $lowInventoryProducts = Product::whereNotNull('minimum_qty')
                ->whereColumn('available_qty', '<=', 'minimum_qty')
                ->get();

            if ($lowInventoryProducts->isEmpty()) {
                Log::info('No low inventory products found');
                return response()->json([
                    'success' => true,
                    'message' => 'No low inventory products found'
                ], 200);
            }

            Log::info('Low inventory products found', [
                'count' => $lowInventoryProducts->count(),
                'products' => $lowInventoryProducts->pluck('name')
            ]);

            // Send email to each admin
            $sentCount = 0;
            $errors = [];
            
            foreach ($adminUsers as $admin) {
                try {
                    Mail::to($admin->email)->send(new LowProductInventoryAlertMail($admin, $lowInventoryProducts));
                    $sentCount++;
                    Log::info("Low product inventory alert sent to {$admin->email}");
                } catch (\Exception $e) {
                    $errors[] = $admin->email;
                    Log::error('Failed to send low product inventory alert', [
                        'email' => $admin->email,
                        'admin_id' => $admin->id,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ]);
                }
            }

            return response()->json([
                'success' => true,
                'message' => "Low product inventory alerts sent successfully to {$sentCount} admin(s)",
                'products_count' => $lowInventoryProducts->count(),
                'admins_notified' => $sentCount,
                'failed_emails' => $errors
            ], 200);

        } catch (\Exception $e) {
            Log::error('Low product inventory alert failed completely', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to send low product inventory alerts',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Send payment reminder alert emails
     */
    public function alertUpcomingPayments()
    {
        try {
            // Get admin users
            $adminUsers = User::role('admin')->get();

            if ($adminUsers->isEmpty()) {
                Log::warning('No admin users found for payment reminder alert');
                return response()->json([
                    'success' => false,
                    'message' => 'No admin users found'
                ], 404);
            }

            $today = Carbon::today();

            // Get purchase orders requiring payment attention
            $purchaseOrders = PurchaseInwardLog::with('vendor')
                ->where('status', '!=', 2)
                ->whereNotNull('receiving_date')
                ->whereNotNull('credit_days')
                ->get();

            $upcomingPayments = [];

            foreach ($purchaseOrders as $order) {
                $orderDate = Carbon::parse($order->receiving_date);
                $paymentDueDate = $orderDate->copy()->addDays($order->credit_days);
                $daysRemaining = $today->diffInDays($paymentDueDate, false);

                // Include orders due within 5 days OR overdue
                if ($daysRemaining <= 5) {
                    $upcomingPayments[] = [
                        'order'            => $order,
                        'vendor'           => $order->vendor ?? null,
                        'order_date'       => $orderDate->format('d-m-Y'),
                        'payment_due_date' => $paymentDueDate->format('d-m-Y'),
                        'days_remaining'   => max(0, $daysRemaining),
                        'is_overdue'       => $daysRemaining < 0,
                        'overdue_days'     => $daysRemaining < 0 ? abs($daysRemaining) : 0,
                        'due_amount'       => $order->due_amount ?? 0,
                    ];
                }
            }

            if (empty($upcomingPayments)) {
                Log::info('No upcoming payments within 5 days');
                return response()->json([
                    'success' => true,
                    'message' => 'No upcoming payments within 5 days'
                ], 200);
            }

            Log::info('Upcoming payments found', [
                'count' => count($upcomingPayments)
            ]);

            // Send email to each admin
            $sentCount = 0;
            $errors = [];
            
            foreach ($adminUsers as $admin) {
                try {
                    Mail::to($admin->email)->send(
                        new PurchasePeriodAlertMail(
                            $admin,
                            $upcomingPayments,
                            $today->format('d M, Y')
                        )
                    );
                    $sentCount++;
                    Log::info("Payment reminder alert sent to {$admin->email}");
                } catch (\Exception $e) {
                    $errors[] = $admin->email;
                    Log::error('Failed to send payment reminder alert', [
                        'email' => $admin->email,
                        'admin_id' => $admin->id,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ]);
                }
            }

            return response()->json([
                'success' => true,
                'message' => "Payment reminder alerts sent successfully to {$sentCount} admin(s)",
                'payments_count' => count($upcomingPayments),
                'admins_notified' => $sentCount,
                'failed_emails' => $errors
            ], 200);

        } catch (\Exception $e) {
            Log::error('Upcoming payment alert failed completely', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to send payment reminder alerts',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Send all alert types
     */
    public function sendAllAlerts()
    {
        $results = [
            'low_inventory' => $this->alertLowInventory()->getData(),
            'low_product_inventory' => $this->alertLowProductInventory()->getData(),
            'payment_reminders' => $this->alertUpcomingPayments()->getData()
        ];

        $allSuccess = $results['low_inventory']->success 
                   && $results['low_product_inventory']->success 
                   && $results['payment_reminders']->success;

        return response()->json([
            'success' => $allSuccess,
            'message' => 'All alerts processed',
            'results' => $results
        ], 200);
    }
    
    public function sendTodayAttendanceToAdmin()
    {
        try {
            $today = date('Y-m-d');
            
           
            $adminUsers = User::role('admin')->get();
            $admin = User::role('admin')->where('email','sumitkrtechie@gmail.com')->first();
            
            if ($adminUsers->isEmpty()) {
                Log::warning('No admin users found for attendance report');
                return response()->json([
                    'success' => false,
                    'message' => 'No admin users found'
                ], 404);
            }
            
            
            $attendance = LabourAttendance::with('labour')
                ->where('date', $today)
                ->orderBy('id', 'desc')
                ->get();
            
            if ($attendance->isEmpty()) {
                Log::info('No attendance records found for today');
                return response()->json([
                    'success' => true,
                    'message' => 'No attendance records found for today'
                ], 200);
            }
            
           
            // Send email to each admin
            $sentCount = 0;
            $errors = [];
            Mail::to('sumitkrtechie@gmail.com')->send(
                        new DailyAttendanceReportMail($admin, $attendance, $today)
                    );
            
            // foreach ($adminUsers as $admin) {
            //     try {
            //         Mail::to($admin->email)->send(
            //             new DailyAttendanceReportMail($admin, $attendance, $today)
            //         );
            //         $sentCount++;
            //         Log::info("Attendance report sent to {$admin->email}");
            //     } catch (\Exception $e) {
            //         $errors[] = $admin->email;
            //         Log::error('Failed to send attendance report', [
            //             'email' => $admin->email,
            //             'error' => $e->getMessage()
            //         ]);
            //     }
            // }
            
            return response()->json([
                'success' => true,
                'message' => "Attendance report sent successfully to {$sentCount} admin(s)",
                'attendance_count' => $attendance->count(),
                'admins_notified' => $sentCount,
                'failed_emails' => $errors,
            ], 200);
            
        } catch (\Exception $e) {
            Log::error('Failed to send attendance report', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to send attendance report',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    public function sendQuotationToCustomer(Request $request)
    {
        try {
            $validated = $request->validate([
                'customer_id'  => 'required|exists:customers,id',
                'quotation_id' => 'required|exists:quotations,id',
            ]);
    
            $customerId  = $validated['customer_id'];
            $quotationId = $validated['quotation_id'];
    
            $company   = GeneralSetting::latest()->first();
            $customer  = Customer::find($customerId);
            $quotation = Quotation::find($quotationId);
            $publicLink = PublicLink::where('entity_id', $quotationId)->where('entity_name','quotation')->first();
    
            if (!$company) {
                return response()->json([
                    'error' => 'Company settings not found.'
                ], 404);
            }
    
            if (!$publicLink) {
                return response()->json([
                    'error' => 'Quotation public link not generated.'
                ], 404);
            }
    
            if ($quotation->customer_id != $customerId) {
                return response()->json([
                    'error' => 'This quotation does not belong to the selected customer.'
                ], 403);
            }
    
            // Send email
            // \Mail::to('sumitkrtechie@gmail.com')->send(
            //     new QuotationMail($customer, $quotation, $company, $publicLink)
            // );
            
            \Mail::to($customer->email)->send(
                new QuotationMail($customer, $quotation, $company, $publicLink)
            );
    
            return response()->json([
                'success' => true,
                'message' => 'Quotation sent successfully to ' . $customer->email,
            ], 200);
    
        } catch (\Exception $e) {
            return response()->json([
                'error'   => 'Failed to send quotation',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
    
    public function sendChallanToCustomer(Request $request)
    {
        try {
            $validated = $request->validate([
                'customer_id'  => 'required|exists:customers,id',
                'bill_id' => 'required|exists:billings,id',
            ]);
    
            $customerId  = $validated['customer_id'];
            $challanId = $validated['bill_id'];
    
            $company   = GeneralSetting::latest()->first();
            $customer  = Customer::find($customerId);
            $challan = Billing::with('customer','product','product.product',
            'shippingAddress','shippingAddress.state')->find($challanId);
            $publicLink = PublicLink::where('entity_id', $challanId)->where('entity_name','challan')->first();
    
            if (!$company) {
                return response()->json([
                    'error' => 'Company settings not found.'
                ], 404);
            }
    
            if (!$publicLink) {
                return response()->json([
                    'error' => 'Challan public link not generated.'
                ], 404);
            }
    
            if ($challan->customer_id != $customerId) {
                return response()->json([
                    'error' => 'This Challan does not belong to the selected customer.'
                ], 403);
            }
    
            // Send email
            
            \Mail::to($customer->email)->send(
                new ChallanMail($customer, $challan, $company, $publicLink)
            );
            // \Mail::to('sumitkrtechie@gmail.com')->send(
            //     new ChallanMail($customer, $challan, $company, $publicLink)
            // );
    
            return response()->json([
                'success' => true,
                'message' => 'Challan sent successfully to ' . $customer->email,
            ], 200);
    
        } catch (\Exception $e) {
            return response()->json([
                'error'   => 'Failed to send challan',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
    
    public function sendPurchaseOrderToVendor(Request $request)
    {
        try {
            $validated = $request->validate([
                'vendor_id'  => 'required|exists:vendors,id',
                'purchase_order_id' => 'required|exists:purchase_orders,id',
            ]);
    
            $vendorId  = $validated['vendor_id'];
            $purchaseOrderId = $validated['purchase_order_id'];
    
            $company   = GeneralSetting::latest()->first();
            $vendor  = Vendor::find($vendorId);
            $purchaseOrder = PurchaseOrder::with(['vendor', 'department', 'inward'])->find($purchaseOrderId);
            $publicLink = PublicLink::where('entity_id', $purchaseOrderId)
                ->where('entity_name','purchase_order')
                ->first();
                $test = ['company' =>$company,
                'vendor' => $vendor,
                'purchaseOrder' =>$purchaseOrder,
                'publicLink' => $publicLink
                ];
    
            if (!$company) {
                return response()->json([
                    'error' => 'Company settings not found.'
                ], 404);
            }
    
            if (!$publicLink) {
                return response()->json([
                    'error' => 'Purchase Order public link not generated.'
                ], 404);
            }
    
            if ($purchaseOrder->vendor_id != $vendorId) {
                return response()->json([
                    'error' => 'This Purchase Order does not belong to the selected Vendor.'
                ], 403);
            }
    
            // Send email
            // \Mail::to('sumitkrtechie@gmail.com')->send(
            //     new PurchaseOrderMail($vendor, $purchaseOrder, $company, $publicLink)
            // );
            // \Mail::to($vendor->email)->send(
            //     new PurchaseOrderMail($vendor, $purchaseOrder, $company, $publicLink)
            // );
    
            return response()->json([
                'success' => true,
                'message' => 'Purchase Order sent successfully to ' . $vendor->email,
                // 'data' => $test,
            ], 200);
    
        } catch (\Exception $e) {
            return response()->json([
                'error'   => 'Failed to send Purchase Order',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}