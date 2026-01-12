<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\MailController;
use App\Http\Controllers\Admin\Modules\Master\DiscardedProductController;
use App\Http\Controllers\Admin\Modules\Master\UserTypeController;
use App\Http\Controllers\Admin\Modules\Master\WorkShiftController;
use App\Http\Controllers\Admin\Modules\Master\DepartmentController;
use App\Http\Controllers\Admin\Modules\Master\GradeController;
use App\Http\Controllers\Admin\Modules\Master\GroupController;
use App\Http\Controllers\Admin\Modules\Master\CategoryController;
use App\Http\Controllers\Admin\Modules\Master\VendorController;
use App\Http\Controllers\Admin\Modules\Master\UnitOfMeasurementController;
use App\Http\Controllers\Admin\Modules\Master\MaterialController;
use App\Http\Controllers\Admin\Modules\Master\ProductController;
use App\Http\Controllers\Admin\Modules\Master\ProductTypeController;
use App\Http\Controllers\Admin\Modules\Master\ProductUnitMaterialController;
use App\Http\Controllers\Admin\Modules\Master\LabourController;
use App\Http\Controllers\Admin\Modules\Master\LabourAttendanceController;
use App\Http\Controllers\Admin\Modules\Master\CustomerController;
use App\Http\Controllers\Admin\Modules\Master\SalesUserController;
use App\Http\Controllers\Admin\Modules\Master\MachineController;
use App\Http\Controllers\Admin\Modules\Master\BranchController;
// use App\Http\Controllers\Admin\Modules\Master\StockController;
use App\Http\Controllers\Admin\Modules\Master\StateController;
use App\Http\Controllers\Admin\Modules\Master\UserController;
use App\Http\Controllers\Admin\Modules\Master\TaxSlabController;
use App\Http\Controllers\Admin\Modules\Master\GeneralSettingController;
use App\Http\Controllers\Admin\Modules\Purchaese\PurchaseOrderController;
use App\Http\Controllers\Admin\Modules\Purchaese\PurchaseInwardLogController;
use App\Http\Controllers\Admin\Modules\Purchaese\PurchaseMaterialController;
use App\Http\Controllers\Admin\Modules\Purchaese\GrnPurchaseController;
use App\Http\Controllers\Admin\Modules\Quotation\QuotationOrderController;
use App\Http\Controllers\Admin\Modules\Quotation\QuotationProductController;
use App\Http\Controllers\Admin\Modules\Production\ProductionOrderController;
use App\Http\Controllers\Admin\Modules\Production\ManageReadyProductController;
use App\Http\Controllers\Admin\Modules\Production\AttachmentController;
use App\Http\Controllers\Admin\Modules\Production\PpMessageController;
use App\Http\Controllers\Admin\Modules\Production\TentativeItemController;
use App\Http\Controllers\Admin\Modules\Production\EmployeeWorksheetController;
use App\Http\Controllers\Admin\Modules\Production\ProductionQualityCheckController;
use App\Http\Controllers\Admin\Modules\Production\RrpController;
use App\Http\Controllers\Admin\Modules\Production\MaterialRequestController;
use App\Http\Controllers\Admin\Modules\Production\PackingSlipController;
use App\Http\Controllers\Admin\Modules\Billing\BillingController;
use App\Http\Controllers\Admin\Modules\Billing\ShippingAddressController;
use App\Http\Controllers\Admin\Modules\HandToolController;
use App\Http\Controllers\Admin\Modules\MachineOperatorController;
use App\Http\Controllers\Admin\Modules\SalesReturnController;
use App\Http\Controllers\Admin\Modules\MaintenanceLogController;
use App\Http\Controllers\Admin\Modules\LogController;
// use App\Http\Controllers\Admin\Modules\TentativeItemController;
use App\Http\Controllers\Admin\Modules\PublicLinkController;
use App\Http\Controllers\Admin\Modules\StockController;
use App\Http\Controllers\Admin\PermissionController;
use App\Http\Controllers\Admin\RoleController;


/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/


Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// Public links

Route::post('get-customer-quotation', [PublicLinkController::class, 'getCustomerQuotation']);
Route::post('get-customer-challan', [PublicLinkController::class, 'getCustomerChallan']);
Route::post('get-vendor-purchaseOrder', [PublicLinkController::class, 'getVendorPurchaseOrder']);

Route::post('by-customer-status-update', [QuotationOrderController::class, 'statusUpdate']);
 Route::post('edit-request', [QuotationOrderController::class, 'reviseQuotation']);

Route::post('test', function(){
    return response()->json(['message' => 'This is a test endpoint']);
});
Route::get('app-details', [GeneralSettingController::class, 'getData']);
Route::post('register', [AuthController::class, 'register']);
Route::post('forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('reset-password', [AuthController::class, 'resetPassword']);
Route::post('update-password/{id}', [AuthController::class, 'updatePassword']);
Route::post('login', [AuthController::class, 'login']);
Route::group(['middleware' => ['api', 'check.jwt'],'prefix' => 'auth'], function ($router) {
    
    Route::post('change-password', [AuthController::class, 'changePassword']);
    Route::post('logout', [AuthController::class, 'logout']);
    Route::post('refresh', [AuthController::class, 'refresh']);
    Route::post('me', [AuthController::class, 'me']);

});

// Admin Master Data
Route::group(['middleware' => ['api', 'check.jwt'],'prefix' => 'admin'], function ($router) {
    
    Route::post('generate-link', [PublicLinkController::class, 'generateLink']);
    Route::post('get-link', [PublicLinkController::class, 'getLink']);
    
    Route::group(['prefix' => 'logs'], function ($router) {
        Route::post('get-production-log', [LogController::class, 'getProductionLog']);
        Route::post('get-vendor-payment-log', [LogController::class, 'getVendorPaymentLog']);
        Route::post('get-customer-payment-log', [LogController::class, 'getCustomerPaymentLog']);
    });
    
    Route::group(['prefix' => 'mail'], function ($router) {
        Route::post('send-low-inventory-mail', [MailController::class, 'alertLowInventory']);
        Route::post('send-payment-reminder-mail', [MailController::class, 'alertUpcomingPayments']);
        Route::post('send-low-product-inventory-mail', [MailController::class, 'alertLowProductInventory']);
        Route::post('send-all', [MailController::class, 'sendAllAlerts']);
        Route::post('daily-attendance-report', [MailController::class, 'sendTodayAttendanceToAdmin']);
        
        // quotation mail
        Route::post('send-quotation-mail',[MailController::class, 'sendQuotationToCustomer']);
        
        // challan mail
        Route::post('send-challan-mail',[MailController::class, 'sendChallanToCustomer']);
        
        // Purchase Order mail
        Route::post('send-purchase-order-mail',[MailController::class, 'sendPurchaseOrderToVendor']);
    });
    
    Route::group(['prefix' => 'userType'], function ($router) {
        Route::get('get-data', [UserTypeController::class, 'getUserType']);
        Route::post('store', [UserTypeController::class, 'store']);
        Route::post('edit/{id}', [UserTypeController::class, 'edit']);
        Route::post('update/{id}', [UserTypeController::class, 'update']);
        Route::post('delete/{id}', [UserTypeController::class, 'delete']);
        Route::post('status-update', [UserTypeController::class, 'statusUpdate']);
        Route::post('search', [UserTypeController::class, 'search']);
    });
    
    Route::group(['prefix' => 'user'], function ($router) {
        Route::get('get-data', [UserController::class, 'getUser']);
        Route::post('store', [UserController::class, 'store']);
        Route::post('edit/{id}', [UserController::class, 'edit']);
        Route::post('update/{id}', [UserController::class, 'update']);
        Route::post('delete/{id}', [UserController::class, 'delete']);
        Route::post('status-update', [UserController::class, 'statusUpdate']);
        Route::get('search', [UserController::class, 'search']);
        Route::post('get-supervisor ', [UserController::class, 'getSupervisor']);
    });


    Route::group(['prefix' => 'department'], function ($router) {
        Route::get('get-data', [DepartmentController::class, 'getData']);
        Route::post('store', [DepartmentController::class, 'store']);
        Route::post('edit/{id}', [DepartmentController::class, 'edit']);
        Route::post('update/{id}', [DepartmentController::class, 'update']);
        Route::post('delete/{id}', [DepartmentController::class, 'delete']);
        Route::post('status-update', [DepartmentController::class, 'statusUpdate']);
        Route::post('search', [DepartmentController::class, 'search']);
         Route::post('sequence-update', [DepartmentController::class, 'sequenceUpdate']);
    });


    Route::group(['prefix' => 'group'], function ($router) {
        Route::get('get-data', [GroupController::class, 'getData']);
        Route::post('store', [GroupController::class, 'store']);
        Route::post('edit/{id}', [GroupController::class, 'edit']);
        Route::post('update/{id}', [GroupController::class, 'update']);
        Route::post('delete/{id}', [GroupController::class, 'delete']);
        Route::post('status-update', [GroupController::class, 'statusUpdate']);
        Route::post('search', [GroupController::class, 'search']);
    });
   
    
    Route::group(['prefix' => 'grade'], function ($router) {
        Route::get('get-data', [GradeController::class, 'getData']);
        Route::post('store', [GradeController::class, 'store']);
        Route::post('edit/{id}', [GradeController::class, 'edit']);
        Route::post('update/{id}', [GradeController::class, 'update']);
        Route::post('delete/{id}', [GradeController::class, 'delete']);
        Route::post('status-update', [GradeController::class, 'statusUpdate']);
        Route::post('search', [GradeController::class, 'search']);
    });
   
    
    Route::group(['prefix' => 'shift'], function ($router) {
        Route::get('get-data', [WorkShiftController::class, 'getData']);
        Route::post('store', [WorkShiftController::class, 'store']);
        Route::post('edit/{id}', [WorkShiftController::class, 'edit']);
        Route::post('update/{id}', [WorkShiftController::class, 'update']);
        Route::post('delete/{id}', [WorkShiftController::class, 'delete']);
        Route::post('status-update', [WorkShiftController::class, 'statusUpdate']);
    });
   
    
    Route::group(['prefix' => 'category'], function ($router) {
        Route::get('get-data', [CategoryController::class, 'getData']);
        Route::post('store', [CategoryController::class, 'store']);
        Route::post('edit/{id}', [CategoryController::class, 'edit']);
        Route::post('update/{id}', [CategoryController::class, 'update']);
        Route::post('delete/{id}', [CategoryController::class, 'delete']);
        Route::post('status-update', [CategoryController::class, 'statusUpdate']);
        Route::post('search', [CategoryController::class, 'search']);
       
    });
   
    
    Route::group(['prefix' => 'vendor'], function ($router) {
        Route::get('get-data', [VendorController::class, 'getData']);
        Route::post('store', [VendorController::class, 'store']);
        Route::post('edit/{id}', [VendorController::class, 'edit']);
        Route::post('update/{id}', [VendorController::class, 'update']);
        Route::post('delete/{id}', [VendorController::class, 'delete']);
        Route::post('status-update', [VendorController::class, 'statusUpdate']);
        Route::post('search', [VendorController::class, 'search']);
        Route::post('get-vendor-ledger', [VendorController::class, 'getLedgerData']);
        Route::post('get-dashboard-data', [VendorController::class, 'getDashboardData']);
    });
   
    
    Route::group(['prefix' => 'unit'], function ($router) {
        Route::get('get-data', [UnitOfMeasurementController::class, 'getData']);
        Route::post('store', [UnitOfMeasurementController::class, 'store']);
        Route::post('edit/{id}', [UnitOfMeasurementController::class, 'edit']);
        Route::post('update/{id}', [UnitOfMeasurementController::class, 'update']);
        Route::post('delete/{id}', [UnitOfMeasurementController::class, 'delete']);
        Route::post('status-update', [UnitOfMeasurementController::class, 'statusUpdate']);
        Route::post('search', [UnitOfMeasurementController::class, 'search']);
    });
   
    
    Route::group(['prefix' => 'material'], function ($router) {
        Route::get('get-data', [MaterialController::class, 'getData']);
        Route::post('store', [MaterialController::class, 'store']);
        Route::post('edit/{id}', [MaterialController::class, 'edit']);
        Route::post('update/{id}', [MaterialController::class, 'update']);
        Route::post('delete/{id}', [MaterialController::class, 'delete']);
        Route::post('status-update', [MaterialController::class, 'statusUpdate']);
        Route::post('search', [MaterialController::class, 'search']);
        // Route::post('get-data-with-availability', [MaterialController::class, 'getDataWithAvailability']);
        Route::post('material-logs', [MaterialController::class, 'materialLogs']);
    });
   
    
    Route::group(['prefix' => 'product'], function ($router) {
        Route::get('get-data', [ProductController::class, 'getData']);
        //  Route::get('get-data', 'ProductController@getData')->middleware('role:admin');
        Route::post('store', [ProductController::class, 'store']);
        Route::post('edit/{id}', [ProductController::class, 'edit']);
        Route::post('update/{id}', [ProductController::class, 'update']);
        Route::post('delete/{id}', [ProductController::class, 'delete']);
        Route::post('status-update', [ProductController::class, 'statusUpdate']);
        Route::post('search', [ProductController::class, 'search']);
    });
    
    
    Route::group(['prefix' => 'discard-product'], function ($router) {
        Route::get('get-data', [DiscardedProductController::class, 'getData']);
        Route::post('remove-from-inventory', [DiscardedProductController::class, 'store']);
        
    });
    
    
    Route::group(['prefix' => 'product-type'], function ($router) {
        Route::get('get-data', [ProductTypeController::class, 'getData']);
        Route::post('store', [ProductTypeController::class, 'store']);
        Route::post('edit/{id}', [ProductTypeController::class, 'edit']);
        Route::post('update/{id}', [ProductTypeController::class, 'update']);
        Route::post('delete/{id}', [ProductTypeController::class, 'delete']);
        Route::post('status-update', [ProductTypeController::class, 'statusUpdate']);
        Route::post('search', [ProductTypeController::class, 'search']);
    });
   
    
    Route::group(['prefix' => 'product-unit-material'], function ($router) {
        Route::get('get-data', [ProductUnitMaterialController::class, 'getData']);
        Route::post('store', [ProductUnitMaterialController::class, 'store']);
        Route::post('edit/{id}', [ProductUnitMaterialController::class, 'edit']);
        Route::post('update/{id}', [ProductUnitMaterialController::class, 'update']);
        Route::post('delete/{id}', [ProductUnitMaterialController::class, 'delete']);
        Route::post('status-update', [ProductUnitMaterialController::class, 'statusUpdate']);
        Route::post('search', [ProductUnitMaterialController::class, 'search']);
    });
   
    
    Route::group(['prefix' => 'labour'], function ($router) {
        Route::get('get-data', [LabourController::class, 'getData']);
        Route::post('store', [LabourController::class, 'store']);
        Route::post('edit/{id}', [LabourController::class, 'edit']);
        Route::post('update/{id}', [LabourController::class, 'update']);
        Route::post('delete/{id}', [LabourController::class, 'delete']);
        Route::post('status-update', [LabourController::class, 'statusUpdate']);
        Route::post('search', [LabourController::class, 'search']);
        
        // Attendance Route
        Route::get('getAttendance', [LabourAttendanceController::class, 'getData']);
        Route::post('markAttendance', [LabourAttendanceController::class, 'markAttendance']);
    });
   
    
    Route::group(['prefix' => 'customer'], function ($router) {
        Route::get('get-data', [CustomerController::class, 'getData']);
        Route::post('store', [CustomerController::class, 'store']);
        Route::post('edit/{id}', [CustomerController::class, 'edit']);
        Route::post('update/{id}', [CustomerController::class, 'update']);
        Route::post('delete/{id}', [CustomerController::class, 'delete']);
        Route::post('status-update', [CustomerController::class, 'statusUpdate']);
        Route::post('search', [CustomerController::class, 'search']);
    });
    
    Route::group(['prefix' => 'state'], function ($router) {
        Route::get('get-data', [StateController::class, 'getData']);
    });
        
   
    
    Route::group(['prefix' => 'sales-user'], function ($router) {
        Route::get('get-data', [SalesUserController::class, 'getData']);
        Route::post('store', [SalesUserController::class, 'store']);
        Route::post('edit/{id}', [SalesUserController::class, 'edit']);
        Route::post('update/{id}', [SalesUserController::class, 'update']);
        Route::post('delete/{id}', [SalesUserController::class, 'delete']);
        Route::post('status-update', [SalesUserController::class, 'statusUpdate']);
        Route::post('search', [SalesUserController::class, 'search']);
    });
   
    
    Route::group(['prefix' => 'machine'], function ($router) {
        Route::get('get-data', [MachineController::class, 'getData']);
        Route::post('store', [MachineController::class, 'store']);
        Route::post('edit/{id}', [MachineController::class, 'edit']);
        Route::post('update/{id}', [MachineController::class, 'update']);
        Route::post('delete/{id}', [MachineController::class, 'delete']);
        Route::post('status-update', [MachineController::class, 'statusUpdate']);
    });
   
    
    Route::group(['prefix' => 'branch'], function ($router) {
        Route::get('get-data', [BranchController::class, 'getData']);
        Route::post('store', [BranchController::class, 'store']);
        Route::post('edit/{id}', [BranchController::class, 'edit']);
        Route::post('update/{id}', [BranchController::class, 'update']);
        Route::post('delete/{id}', [BranchController::class, 'delete']);
        Route::post('status-update', [BranchController::class, 'statusUpdate']);
        Route::post('search', [BranchController::class, 'search']);
    });
   
    
    Route::group(['prefix' => 'setting'], function ($router) {
        Route::get('get-data', [GeneralSettingController::class, 'getData']);
        Route::post('update/{id}', [GeneralSettingController::class, 'update']);
    });
    
    
    Route::group(['prefix' => 'tax-slab'], function ($router) {
        Route::get('get-data', [TaxSlabController::class, 'getData']);
        Route::post('store', [TaxSlabController::class, 'store']);
        Route::post('edit/{id}', [TaxSlabController::class, 'edit']);
        Route::post('update/{id}', [TaxSlabController::class, 'update']);
        Route::post('delete/{id}', [TaxSlabController::class, 'delete']);
        Route::post('status-update', [TaxSlabController::class, 'statusUpdate']);
        Route::post('search', [TaxSlabController::class, 'search']);
    });
   
    
    // Route::group(['prefix' => 'stock'], function ($router) {
    //     Route::get('get-data', [StockController::class, 'getData']);
    //     Route::post('store', [StockController::class, 'store']);
    //     Route::post('edit/{id}', [StockController::class, 'edit']);
    //     Route::post('update/{id}', [StockController::class, 'update']);
    //     Route::post('delete/{id}', [StockController::class, 'delete']);
    //     Route::post('status-update', [StockController::class, 'statusUpdate']);
    //     Route::post('search', [StockController::class, 'search']);
    // });


    Route::group(['prefix' => 'purchase-order'], function ($router) {
        Route::get('get-data', [PurchaseOrderController::class, 'getData']);
        Route::post('store', [PurchaseOrderController::class, 'store']);
        Route::post('edit/{id}', [PurchaseOrderController::class, 'edit']);
        Route::post('update/{id}', [PurchaseOrderController::class, 'update']);
        Route::post('delete/{id}', [PurchaseOrderController::class, 'delete']);
        Route::post('status-update', [PurchaseOrderController::class, 'statusUpdate']);
        Route::post('getApprovePOData', [PurchaseOrderController::class, 'getApprovePOData']);
        Route::post('approvePO', [PurchaseOrderController::class, 'approvePO']);
        Route::post('search', [PurchaseOrderController::class, 'search']);
    });


    Route::group(['prefix' => 'purchase-inward'], function ($router) {
        Route::get('get-data', [PurchaseInwardLogController::class, 'getData']);
        Route::post('store', [PurchaseInwardLogController::class, 'store']);
        Route::post('edit/{id}', [PurchaseInwardLogController::class, 'edit']);
        Route::post('update/{id}', [PurchaseInwardLogController::class, 'update']);
        Route::post('delete/{id}', [PurchaseInwardLogController::class, 'delete']);
        Route::post('status-update', [PurchaseInwardLogController::class, 'statusUpdate']);
        Route::post('search', [PurchaseInwardLogController::class, 'search']);
        Route::post('store-payment-record', [PurchaseInwardLogController::class, 'storeInwardPayment']);
        Route::post('get-paymentData', [PurchaseInwardLogController::class, 'getPaymentData']);
        
        Route::post('upload-invoice/{id}', [PurchaseInwardLogController::class, 'uploadInvoice']);
    });


    Route::group(['prefix' => 'purchase-material'], function ($router) {
        Route::get('get-data', [PurchaseMaterialController::class, 'getData']);
        Route::post('store', [PurchaseMaterialController::class, 'store']);
        Route::post('edit/{id}', [PurchaseMaterialController::class, 'edit']);
        Route::post('update/{id}', [PurchaseMaterialController::class, 'update']);
        Route::post('delete/{id}', [PurchaseMaterialController::class, 'delete']);
        Route::post('status-update', [PurchaseMaterialController::class, 'statusUpdate']);
        Route::post('search', [PurchaseMaterialController::class, 'search']);
    });


    Route::group(['prefix' => 'quotation-order'], function ($router) {
        Route::get('get-data', [QuotationOrderController::class, 'getData']);
        Route::post('store', [QuotationOrderController::class, 'store']);
        Route::post('edit/{id}', [QuotationOrderController::class, 'edit']);
        Route::post('update/{id}', [QuotationOrderController::class, 'update']);
        Route::post('delete/{id}', [QuotationOrderController::class, 'delete']);
        Route::post('status-update', [QuotationOrderController::class, 'statusUpdate']);
        Route::post('get-quotation-data', [QuotationOrderController::class, 'getQuotationData']);
        
    });


    Route::group(['prefix' => 'quotation-product'], function ($router) {
        Route::get('get-data', [QuotationProductController::class, 'getData']);
        Route::post('store', [QuotationProductController::class, 'store']);
        Route::post('edit/{id}', [QuotationProductController::class, 'edit']);
        Route::post('update/{id}', [QuotationProductController::class, 'update']);
        Route::post('delete/{id}', [QuotationProductController::class, 'delete']);
        Route::post('status-update', [QuotationProductController::class, 'statusUpdate']);
       
    });


    Route::group(['prefix' => 'production-order'], function ($router) {
        Route::get('get-data', [ProductionOrderController::class, 'getData']);
        Route::post('store', [ProductionOrderController::class, 'store']);
        Route::post('edit/{id}', [ProductionOrderController::class, 'edit']);
        Route::post('update/{id}', [ProductionOrderController::class, 'update']);
        Route::post('delete/{id}', [ProductionOrderController::class, 'delete']);
        Route::post('status-update', [ProductionOrderController::class, 'statusUpdate']);
        Route::post('approve-all-product', [ProductionOrderController::class, 'ApproveAllProduct']);
        Route::post('approve-single-product', [ProductionOrderController::class, 'ApproveSingleProduct']);
        Route::post('get-previous-po', [ProductionOrderController::class, 'getPreviousPO']);
        Route::post('store-own-production-product', [ProductionOrderController::class, 'storeOwnProductionProduct']);
        
        
        Route::post('upload-document', [AttachmentController::class, 'uploadDocument']);
        Route::post('upload-message', [PpMessageController::class, 'uploadMessage']);
        
        
        Route::post('product-production-log', [ProductionOrderController::class, 'productProductionLog']);
        
        
        //in production routes
        Route::post('get-production-batch', [ProductionOrderController::class, 'productionBatches']);
        
        Route::post('set-change-department', [ProductionOrderController::class, 'changeDepartment']);
        
        Route::post('failed-qc', [ProductionOrderController::class, 'failedQc']);
        
        Route::post('set-updated-value', [ProductionOrderController::class, 'setUpdatedValue']);
        
        Route::post('get-batch-products', [ProductionOrderController::class, 'batchProducts']);
        Route::post('mark-ready-for-delivey', [ProductionOrderController::class, 'markReadyForDelivery']);
        
        Route::post('store-tentative-items', [TentativeItemController::class, 'storeAndUpdate']);
        
        
        Route::post('store-worksheet', [EmployeeWorksheetController::class, 'storeWorksheet']);
        Route::post('get-all-worksheet', [EmployeeWorksheetController::class, 'getAllWorksheet']);
        
        //Material Request Controller
        Route::post('get-material-request', [MaterialRequestController::class, 'getMaterialRequestData']);
        Route::post('get-all-material-request', [MaterialRequestController::class, 'getAllMaterialRequestData']);
        Route::post('approve-material-request', [MaterialRequestController::class, 'approveRequest']);
        Route::post('store-material-request', [MaterialRequestController::class, 'store']);
        
        
        //
        Route::post('calculate-rrp', [RrpController::class, 'store']);
        
        
        
    });
    
    Route::group(['prefix' => 'store-order'], function ($router) {
        Route::get('get-ready-product', [ManageReadyProductController::class, 'getReadyProduct']);
        Route::post('get-challan-byId', [ManageReadyProductController::class, 'getChallanById']);
        
        
    });
   

    Route::group(['prefix' => 'hand-tool'], function ($router) {
        Route::get('get-data', [HandToolController::class, 'getData']);
        Route::post('store', [HandToolController::class, 'store']);
        Route::post('edit/{id}', [HandToolController::class, 'edit']);
        Route::post('update/{id}', [HandToolController::class, 'update']);
        Route::post('delete/{id}', [HandToolController::class, 'delete']);
        Route::post('status-update', [HandToolController::class, 'statusUpdate']);
        Route::post('search', [HandToolController::class, 'search']);
    });


    Route::group(['prefix' => 'machine-operator'], function ($router) {
        Route::get('get-data', [MachineOperatorController::class, 'getData']);
        Route::post('store', [MachineOperatorController::class, 'store']);
        Route::post('edit/{id}', [MachineOperatorController::class, 'edit']);
        Route::post('update/{id}', [MachineOperatorController::class, 'update']);
        Route::post('delete/{id}', [MachineOperatorController::class, 'delete']);
        Route::post('status-update', [MachineOperatorController::class, 'statusUpdate']);
        Route::post('search', [MachineOperatorController::class, 'search']);
    });


    Route::group(['prefix' => 'sales-return'], function ($router) {
        Route::get('get-data', [SalesReturnController::class, 'getData']);
        Route::post('store', [SalesReturnController::class, 'store']);
        Route::post('edit/{id}', [SalesReturnController::class, 'edit']);
        Route::post('update/{id}', [SalesReturnController::class, 'update']);
        Route::post('delete/{id}', [SalesReturnController::class, 'delete']);
        Route::post('status-update', [SalesReturnController::class, 'statusUpdate']);
        Route::post('search', [SalesReturnController::class, 'search']);
    });


    Route::group(['prefix' => 'maintenance-log'], function ($router) {
        Route::get('get-data', [MaintenanceLogController::class, 'getData']);
        Route::post('store', [MaintenanceLogController::class, 'store']);
        Route::post('edit/{id}', [MaintenanceLogController::class, 'edit']);
        Route::post('update/{id}', [MaintenanceLogController::class, 'update']);
        Route::post('delete/{id}', [MaintenanceLogController::class, 'delete']);
        Route::post('status-update', [MaintenanceLogController::class, 'statusUpdate']);
        Route::post('search', [MaintenanceLogController::class, 'search']);
    });


    Route::group(['prefix' => 'tentative-item'], function ($router) {
        Route::get('get-data', [TentativeItemController::class, 'getData']);
        Route::post('store', [TentativeItemController::class, 'store']);
        Route::post('edit/{id}', [TentativeItemController::class, 'edit']);
        Route::post('update/{id}', [TentativeItemController::class, 'update']);
        Route::post('delete/{id}', [TentativeItemController::class, 'delete']);
        Route::post('status-update', [TentativeItemController::class, 'statusUpdate']);
        Route::post('search', [TentativeItemController::class, 'search']);
    });


    Route::group(['prefix' => 'quality-check'], function ($router) {
        Route::get('get-data', [ProductionQualityCheckController::class, 'getData']);
        Route::post('store', [ProductionQualityCheckController::class, 'store']);
        Route::post('edit/{id}', [ProductionQualityCheckController::class, 'edit']);
        Route::post('update/{id}', [ProductionQualityCheckController::class, 'update']);
        Route::post('delete/{id}', [ProductionQualityCheckController::class, 'delete']);
        Route::post('status-update', [ProductionQualityCheckController::class, 'statusUpdate']);
        Route::post('search', [ProductionQualityCheckController::class, 'search']);
    });


    Route::group(['prefix' => 'shipping'], function ($router) {
        Route::get('get-data/{customer_id}', [ShippingAddressController::class, 'getData']);
        Route::post('store', [ShippingAddressController::class, 'store']);
        Route::post('update/{id}', [ShippingAddressController::class, 'update']);
        Route::post('delete/{id}', [ShippingAddressController::class, 'delete']);
    });


    Route::group(['prefix' => 'billing'], function ($router) {
        Route::get('get-data', [BillingController::class, 'getData']);
        Route::post('store', [BillingController::class, 'store']);
        Route::post('edit/{id}', [BillingController::class, 'edit']);
        Route::post('update/{id}', [BillingController::class, 'update']);
        Route::post('delete/{id}', [BillingController::class, 'delete']);
        Route::post('dispatch-product', [BillingController::class, 'dispatchProduct']);
        Route::post('status-update', [BillingController::class, 'statusUpdate']);
        Route::post('mark-as-delivered', [BillingController::class, 'markAsDelivered']);
        
        // customer payment
        Route::post('store-customer-payment', [BillingController::class, 'storeCustomerPayment']);
        
        Route::post('get-payment-data', [BillingController::class, 'getPaymentData']);
        
        Route::post('get-ledger-data', [BillingController::class, 'getLedgerData']);
    });


    Route::group(['prefix' => 'stock'], function ($router) {
        Route::get('get-data', [StockController::class, 'getData']);
    });


    Route::group(['prefix' => 'grn-purchase'], function ($router) {
        Route::get('get-data', [GrnPurchaseController::class, 'getData']);
        Route::post('store', [GrnPurchaseController::class, 'store']);
        Route::post('edit/{id}', [GrnPurchaseController::class, 'edit']);
        Route::post('update/{id}', [GrnPurchaseController::class, 'update']);
        Route::post('delete/{id}', [GrnPurchaseController::class, 'delete']);
        Route::post('status-update', [GrnPurchaseController::class, 'statusUpdate']);
    });


    Route::group(['prefix' => 'packing-slip'], function ($router) {
        Route::get('get-data', [PackingSlipController::class, 'getData']);
        Route::post('store', [PackingSlipController::class, 'store']);
        Route::post('edit/{id}', [PackingSlipController::class, 'edit']);
        Route::post('update/{id}', [PackingSlipController::class, 'update']);
        Route::post('delete/{id}', [PackingSlipController::class, 'delete']);
        Route::post('status-update', [PackingSlipController::class, 'statusUpdate']);
        Route::post('search', [PackingSlipController::class, 'search']);
    });

    
    Route::group(['prefix' => 'user-permissions'], function ($router) {
        Route::get('get-data', [PermissionController::class, 'getData']);
        Route::post('store', [PermissionController::class, 'store']);
        Route::post('update/{id}', [PermissionController::class, 'update']);
        Route::post('delete/{id}', [PermissionController::class, 'destroy']);
        Route::post('get-data-by-module', [PermissionController::class, 'getDataByModule']);
        Route::post('get-module-permission', [PermissionController::class, 'getModulePermission']);
    });

    
    Route::group(['prefix' => 'roles'], function ($router) {
        Route::get('get-data', [RoleController::class, 'getData']);
        Route::post('store', [RoleController::class, 'store']);
        Route::post('update/{id}', [RoleController::class, 'update']);
        Route::post('delete/{id}', [RoleController::class, 'destroy']);
        Route::post('status-update', [RoleController::class, 'statusUpdate']);
        Route::post('assign-permissions', [RoleController::class, 'assignPermission']);
    });

});
