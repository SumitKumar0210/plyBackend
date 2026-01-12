<?php

namespace App\Http\Controllers\Admin\Modules;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\PublicLink;
use App\Models\Quotation;
use App\Models\Billing;
use App\Models\PurchaseOrder;
use Illuminate\Support\Str;
use Carbon\Carbon;

class PublicLinkController extends Controller
{
    private function getEntityModel($entityType)
    {
        $models = [
            'quotation' => Quotation::class,
            'challan' => Billing::class,
            'purchase_order' => PurchaseOrder::class,
        ];

        return $models[$entityType] ?? null;
    }

    // private function getEntityIdField($entityType)
    // {
    //     $fields = [
    //         'quotation' => 'quotation_id',
    //         'challan' => 'bill_id',
    //         'purchase_order' => 'production_order_id',
    //     ];

    //     return $fields[$entityType] ?? null;
    // }

    private function getEntityTableName($entityType)
    {
        $tables = [
            'quotation' => 'quotations',
            'challan' => 'billings',
            'purchase_order' => 'purchase_orders',
        ];

        return $tables[$entityType] ?? null;
    }

    public function generateLink(Request $request)
    {
        try {
            $validated = $request->validate([
                'id' => 'required|integer',
                'entity' => 'required|string|in:quotation,challan,purchase_order',
                'expiry_days' => 'nullable|integer|min:1|max:365',
            ]);

            $entityType = $validated['entity'];
            $entityId = $validated['id'];
            $days = $validated['expiry_days'] ?? 1;
            
            // Validate entity exists
            $tableName = $this->getEntityTableName($entityType);
            $request->validate([
                'id' => "exists:{$tableName},id",
            ]);

            $expiryTime = Carbon::now()->addDays($days);

            // Check for existing active link
            $existingLink = PublicLink::where('entity_name', $entityType)
                ->where('entity_id',$entityId)
                ->where('expiry_time', '>', Carbon::now())
                ->latest()
                ->first();

            if ($existingLink) {
                return response()->json([
                    'success' => true, 
                    'message' => "An active public link already exists for this {$entityType}.",
                    'data' => [
                        'link' => $existingLink->link,
                        'expiry_time' => $existingLink->expiry_time,
                    ],
                ], 200);
            }

            // Create new link
            $link = new PublicLink();
            $link->entity_id = $entityId;
            $link->entity_name = $entityType;
            $link->link = Str::random(40);
            $link->expiry_time = $expiryTime;
            $link->view_count = 0;
            $link->save();

            return response()->json([
                'success' => true,
                'message' => "Public link generated successfully for {$days} day(s).",
                'data' => [
                    'link' => $link->link,
                    'expiry_time' => $link->expiry_time,
                ],
            ], 201);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to generate public link',
                'details' => $e->getMessage(),
            ], 500);
        }
    }

    public function getLink(Request $request)
    {
        try {
            $validated = $request->validate([
                'id' => 'required|integer',
                'entity' => 'required|string|in:quotation,challan,purchase_order',
            ]);

            $entityType = $validated['entity'];
            $entityId = $validated['id'];

            $link = PublicLink::where('entity_id', $entityId)
                ->where('entity_name', $entityType)
                ->latest()
                ->first();

            if (!$link) {
                return response()->json([
                    'success' => false,
                    'message' => "No public link found for this {$entityType}.",
                ], 200);
            }

            // Check if expired
            if (Carbon::parse($link->expiry_time)->isPast()) {
                return response()->json([
                    'success' => false,
                    'message' => 'The public link has expired.',
                    'data' => [
                        'link' => $link->link,
                        'expired_at' => $link->expiry_time,
                    ],
                ], 410);
            }

            return response()->json([
                'success' => true,
                'message' => 'Public link is valid and active.',
                'data' => [
                    'link' => $link->link,
                    'expiry_time' => $link->expiry_time,
                    'view_count' => $link->view_count,
                ],
            ], 200);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to fetch public link',
                'details' => $e->getMessage(),
            ], 500);
        }
    }
    
    // public function getCustomerData(Request $request)
    // {
    //     try {
    //         $link = Str::afterLast($request->link, '/');
    //         $publicLink = PublicLink::where('link', $link)->first();
    
    //         if (!$publicLink) {
    //             return response()->json([
    //                 'success' => false,
    //                 'message' => 'No public link found.',
    //             ], 404);
    //         }
    
    //         // Check expiry
    //         if (Carbon::parse($publicLink->expiry_time)->isPast()) {
    //             return response()->json([
    //                 'success' => false,
    //                 'message' => 'The public link has expired.',
    //                 'data' => [
    //                     'link' => $publicLink->link,
    //                     'expired_at' => $publicLink->expiry_time,
    //                 ],
    //             ], 410);
    //         }
    
    //         $entityType = $publicLink->entity_name;
    //         $data = null;

    //         // Fetch entity based on type
    //         switch ($entityType) {
    //             case 'quotation':
    //                 $data = Quotation::with('customer', 'customer.state')
    //                     ->find($publicLink->quotation_id);
    //                 break;
    //             case 'challan':
    //                 $data = Billing::with('customer', 'customer.state')
    //                     ->find($publicLink->bill_id);
    //                 break;
    //             case 'purchase_order':
    //                 $data = PurchaseOrder::with('vendor')
    //                     ->find($publicLink->production_order_id);
    //                 break;
    //         }
    
    //         if (!$data) {
    //             return response()->json([
    //                 'success' => false,
    //                 'message' => "No {$entityType} found for this link.",
    //             ], 404);
    //         }

    //         // Increment view count
    //         $publicLink->increment('view_count');
    
    //         return response()->json([
    //             'success' => true,
    //             'message' => 'Public link is valid and active.',
    //             'data' => [
    //                 'entity_name' => $entityType,
    //                 'entity_data' => $data,
    //             ],
    //         ], 200);
    //     } catch (\Throwable $e) {
    //         return response()->json([
    //             'success' => false,
    //             'error' => 'Failed to fetch data',
    //             'details' => $e->getMessage(),
    //         ], 500);
    //     }
    // }
    
    public function getCustomerQuotation(Request $request)
    {
        try {
            // Validate that the link exists in public_links table, not quotations
            
            $link =Str::afterLast($request->link, '/');
            $publicLink = PublicLink::where('link', $link)->first();
    
            if (!$publicLink) {
                return response()->json([
                    'success' => false,
                    'message' => 'No public link found for this quotation.',
                ], 404);
            }
    
            // Check expiry time
            if (Carbon::parse($publicLink->expiry_time)->isPast()) {
                return response()->json([
                    'success' => false,
                    'message' => 'The public link has expired.',
                    'data' => [
                        'link' => $publicLink->link,
                        'expired_at' => $publicLink->expiry_time,
                    ],
                ], 410);
            }
    
            // Fetch the related quotation
            $quotation = Quotation::with('customer', 'customer.state')->find($publicLink->entity_id);
    
            if (!$quotation) {
                return response()->json([
                    'success' => false,
                    'message' => 'No quotation found for this link.',
                ], 404);
            }
            
            $publicLink->increment('view_count');
    
            return response()->json([
                'success' => true,
                'message' => 'Public link is valid and active.',
                'data' => $quotation,
            ], 200);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to fetch public link',
                'details' => $e->getMessage(),
            ], 500);
        }
    }
    
    public function getCustomerChallan(Request $request)
    {
        try {
            // Validate that the link exists in public_links table, not quotations
            
            $link =Str::afterLast($request->link, '/');
            $publicLink = PublicLink::where('link', $link)->first();
    
            if (!$publicLink) {
                return response()->json([
                    'success' => false,
                    'message' => 'No public link found for this quotation.',
                ], 404);
            }
    
            // Check expiry time
            if (Carbon::parse($publicLink->expiry_time)->isPast()) {
                return response()->json([
                    'success' => false,
                    'message' => 'The public link has expired.',
                    'data' => [
                        'link' => $publicLink->link,
                        'expired_at' => $publicLink->expiry_time,
                    ],
                ], 410);
            }
    
            // Fetch the related Challan
            $challan = Billing::with('customer','product','product.product',
            'shippingAddress','shippingAddress.state')->find($publicLink->entity_id);
    
            if (!$challan) {
                return response()->json([
                    'success' => false,
                    'message' => 'No challan found for this link.',
                ], 404);
            }
            
            $publicLink->increment('view_count');
    
            return response()->json([
                'success' => true,
                'message' => 'Public link is valid and active.',
                'data' => $challan,
            ], 200);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to fetch public link',
                'details' => $e->getMessage(),
            ], 500);
        }
    }
    
    public function getVendorPurchaseOrder(Request $request)
    {
        try {
            // Validate that the link exists in public_links table, not quotations
            
            $link =Str::afterLast($request->link, '/');
            $publicLink = PublicLink::where('link', $link)->first();
    
            if (!$publicLink) {
                return response()->json([
                    'success' => false,
                    'message' => 'No public link found for this quotation.',
                ], 404);
            }
    
            // Check expiry time
            if (Carbon::parse($publicLink->expiry_time)->isPast()) {
                return response()->json([
                    'success' => false,
                    'message' => 'The public link has expired.',
                    'data' => [
                        'link' => $publicLink->link,
                        'expired_at' => $publicLink->expiry_time,
                    ],
                ], 410);
            }
    
            // Fetch the related Challan
            $purchaseOrder = PurchaseOrder::with(['vendor', 'department'])->find($publicLink->entity_id);
    
            if (!$purchaseOrder) {
                return response()->json([
                    'success' => false,
                    'message' => 'No purchase order found for this link.',
                ], 404);
            }
            $purchaseOrder->material_items = is_string($purchaseOrder->material_items)
                ? json_decode($purchaseOrder->material_items, true)
                : $purchaseOrder->material_items;
                
                $publicLink->increment('view_count');
    
            return response()->json([
                'success' => true,
                'message' => 'Public link is valid and active.',
                'data' => $purchaseOrder,
            ], 200);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to fetch public link',
                'details' => $e->getMessage(),
            ], 500);
        }
    }
}