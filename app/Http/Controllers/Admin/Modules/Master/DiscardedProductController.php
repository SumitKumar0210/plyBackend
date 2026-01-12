<?php

namespace App\Http\Controllers\Admin\Modules\Master;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\DiscardedProduct;
use App\Models\Product;
use App\Models\Stock;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Exception;

class DiscardedProductController extends Controller
{
  
    public function getData(Request $request)
    {
        try {
            $perPage = $request->input('per_page', 10);
            $page    = $request->input('page', 1);
            $search  = $request->input('search', '');
            $status  = $request->input('status', null);
    
            $query = DiscardedProduct::query()
                ->with([
                    'product:id,name,model,image',
                    'user:id,name'
                ])
                ->select([
                    'discarded_products.*',
                    DB::raw('products.name as product_name'),
                    DB::raw('products.model as product_model'),
                    DB::raw('users.name as user_name'),
                ])
                ->leftJoin('products', 'discarded_products.product_id', '=', 'products.id')
                ->leftJoin('users', 'discarded_products.action_by', '=', 'users.id');
    
            /* SEARCH FILTER */
            if (!empty($search)) {
                $query->where(function ($q) use ($search) {
                    $q->where('products.name', 'LIKE', "%{$search}%")
                      ->orWhere('products.model', 'LIKE', "%{$search}%")
                      ->orWhere('discarded_products.qty', 'LIKE', "%{$search}%")
                      ->orWhere('users.name', 'LIKE', "%{$search}%"); // ✅ USER NAME
                });
            }
    
            /* STATUS FILTER */
            if (!is_null($status)) {
                $query->where('discarded_products.revised', $status);
            }
    
            $query->orderBy('discarded_products.created_at', 'desc');
    
            $discardedProducts = $query->paginate($perPage, ['*'], 'page', $page);
    
            return response()->json([
                'success'       => true,
                'message'       => 'Discarded products retrieved successfully',
                'data'          => $discardedProducts->items(),
                'total'         => $discardedProducts->total(),
                'current_page'  => $discardedProducts->currentPage(),
                'per_page'      => $discardedProducts->perPage(),
                'last_page'     => $discardedProducts->lastPage(),
            ], 200);
    
        } catch (\Exception $e) {
    
            Log::error('Error fetching discarded products', [
                'message' => $e->getMessage(),
                'file'    => $e->getFile(),
                'line'    => $e->getLine(),
            ]);
    
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve discarded products',
                'error'   => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }


    
    public function store(Request $request)
{
    
    // Validation
    $validator = Validator::make($request->all(), [
        'product_id' => [
            'required',
            'integer',
            Rule::exists('products', 'id')->where(function ($query) {
                $query->whereNull('deleted_at');
            }),
        ],
        'qty' => 'required|integer|min:1',
        'remark' => 'required|string|min:10|max:500',
    ], [
        'product_id.required' => 'Product is required',
        'product_id.exists' => 'Selected product does not exist',
        'qty.required' => 'Quantity is required',
        'qty.min' => 'Quantity must be at least 1',
        'remark.required' => 'Remark is required',
        'remark.min' => 'Remark must be at least 10 characters',
        'remark.max' => 'Remark cannot exceed 500 characters',
    ]);

    if ($validator->fails()) {
        return response()->json([
            'success' => false,
            'message' => 'Validation error',
            'errors' => $validator->errors(),
        ], 422);
    }

    DB::beginTransaction();
    
    try {
        // Find product with lock to prevent race conditions
        $product = Product::lockForUpdate()->find($request->product_id);
        
        if (!$product) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Product not found',
            ], 404);
        }
        
        // Check available stock
        if ($product->available_qty < $request->qty) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => "Insufficient stock. Available: {$product->available_qty}, Requested: {$request->qty}",
            ], 400);
        }
        
        // Calculate new available quantity
        $newAvailableQty = $product->available_qty - $request->qty;
        
        // Create discarded product record
        $discardedProduct = DiscardedProduct::create([
            'Product_id' => $request->product_id,
            'qty' => $request->qty,
            'remark' => $request->remark,
            'date' => now(),
            'action_by' => auth()->id(),
            'revised' => 0,
        ]);
        
        // Create stock record for audit trail
        Stock::create([
            'product_id' => $request->product_id,
            'out_stock' => $request->qty,
            'in_stock' => 0,
            'available_qty' => $newAvailableQty,
            'remark' => $request->remark,
        ]);
        
        // Update product available quantity
        $product->available_qty = $newAvailableQty;
        $product->save();
        
        DB::commit();
        
        \Log::info('Stock discarded successfully', [
            'product_id' => $product->id,
            'qty_discarded' => $request->qty,
            'new_available_qty' => $newAvailableQty,
            'action_by' => auth()->id(),
        ]);
        
        return response()->json([
            'success' => true,
            'message' => "{$request->qty} unit(s) of {$product->name} discarded successfully",
            'data' => [
                'product' => $product->fresh(), 
                'discarded_product' => $discardedProduct,
            ],
        ], 201);
        
    } catch (Exception $e) {
        DB::rollBack();
        
        \Log::error('Error discarding stock: ' . $e->getMessage(), [
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString(),
            'request' => $request->all(),
        ]);
        
        return response()->json([
            'success' => false,
            'message' => 'Failed to discard stock',
            'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
        ], 500);
    }
}

    public function edit(Request $request, $id)
    {
        try {
            $discardedProduct = DiscardedProduct::with(['product:id,name,model,image'])
                ->find($id);

            if (!$discardedProduct) {
                return response()->json([
                    'success' => false,
                    'message' => 'Discarded product not found',
                ], 404);
            }

            // Check if product is already revised
            if ($discardedProduct->revised == 1) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot edit a revised discarded product',
                ], 400);
            }

            return response()->json([
                'success' => true,
                'message' => 'Discarded product retrieved successfully',
                'data' => $discardedProduct,
            ], 200);

        } catch (Exception $e) {
            Log::error('Error fetching discarded product: ' . $e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'id' => $id,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve discarded product',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }
    
    

    // public function update(Request $request)
    // {
    //     // Validation
    //     $validator = Validator::make($request->all(), [
    //         'id' => 'required|integer|exists:discarded_products,id',
    //         'product_id' => [
    //             'required',
    //             'integer',
    //             Rule::exists('products', 'id')->where(function ($query) {
    //                 $query->whereNull('deleted_at');
    //             }),
    //         ],
    //         'qty' => 'required|integer|min:1',
    //         'date' => 'required|date|before_or_equal:today',
    //         'action_by' => 'required|string|max:255',
    //     ], [
    //         'id.required' => 'Discarded product ID is required',
    //         'id.exists' => 'Discarded product not found',
    //         'product_id.required' => 'Product is required',
    //         'product_id.exists' => 'Selected product does not exist',
    //         'qty.required' => 'Quantity is required',
    //         'qty.min' => 'Quantity must be at least 1',
    //         'date.required' => 'Date is required',
    //         'date.before_or_equal' => 'Date cannot be in the future',
    //         'action_by.required' => 'Action by field is required',
    //     ]);

    //     if ($validator->fails()) {
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Validation error',
    //             'errors' => $validator->errors(),
    //         ], 422);
    //     }

    //     DB::beginTransaction();

    //     try {
    //         $discardedProduct = DiscardedProduct::findOrFail($request->id);

    //         // Check if product is already revised
    //         if ($discardedProduct->revised == 1) {
    //             return response()->json([
    //                 'success' => false,
    //                 'message' => 'Cannot update a revised discarded product',
    //             ], 400);
    //         }

    //         $oldQty = $discardedProduct->qty;
    //         $oldProductId = $discardedProduct->product_id;
    //         $newQty = $request->qty;
    //         $newProductId = $request->product_id;

    //         // If product changed or quantity changed, update stock
    //         if ($oldProductId != $newProductId || $oldQty != $newQty) {
                
    //             // Revert old stock entry
    //             $oldStock = Stock::where('product_id', $oldProductId)->first();
    //             if ($oldStock) {
    //                 $oldStock->out_stock -= $oldQty;
    //                 $oldStock->updated_at = now();
    //                 $oldStock->save();
    //             }

    //             // Check new product stock availability
    //             $newStock = Stock::where('product_id', $newProductId)->first();
                
    //             if (!$newStock) {
    //                 DB::rollBack();
    //                 return response()->json([
    //                     'success' => false,
    //                     'message' => 'No stock found for the selected product',
    //                 ], 400);
    //             }

    //             $availableStock = $newStock->in_stock - $newStock->out_stock;
                
    //             if ($availableStock < $newQty) {
    //                 DB::rollBack();
    //                 return response()->json([
    //                     'success' => false,
    //                     'message' => "Insufficient stock. Available: {$availableStock}, Requested: {$newQty}",
    //                 ], 400);
    //             }

    //             // Update new stock
    //             $newStock->out_stock += $newQty;
    //             $newStock->updated_at = now();
    //             $newStock->save();
    //         }

    //         // Update discarded product record
    //         $discardedProduct->update([
    //             'product_id' => $request->product_id,
    //             'qty' => $request->qty,
    //             'date' => $request->date,
    //             'action_by' => $request->action_by,
    //             'updated_at' => now(),
    //         ]);

    //         DB::commit();

    //         // Load product relation for response
    //         $discardedProduct->load('product:id,name,model,image');

    //         return response()->json([
    //             'success' => true,
    //             'message' => 'Discarded product updated successfully',
    //             'data' => $discardedProduct,
    //         ], 200);

    //     } catch (Exception $e) {
    //         DB::rollBack();
            
    //         Log::error('Error updating discarded product: ' . $e->getMessage(), [
    //             'file' => $e->getFile(),
    //             'line' => $e->getLine(),
    //             'request' => $request->all(),
    //         ]);

    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Failed to update discarded product',
    //             'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
    //         ], 500);
    //     }
    // }

    
    // public function reviseProduct(Request $request)
    // {
    //     // Validation
    //     $validator = Validator::make($request->all(), [
    //         'id' => 'required|integer|exists:discarded_products,id',
    //         'revised_qty' => 'nullable|integer|min:0',
    //     ], [
    //         'id.required' => 'Discarded product ID is required',
    //         'id.exists' => 'Discarded product not found',
    //         'revised_qty.integer' => 'Revised quantity must be a valid number',
    //         'revised_qty.min' => 'Revised quantity cannot be negative',
    //     ]);

    //     if ($validator->fails()) {
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Validation error',
    //             'errors' => $validator->errors(),
    //         ], 422);
    //     }

    //     DB::beginTransaction();

    //     try {
    //         $discardedProduct = DiscardedProduct::findOrFail($request->id);

    //         // Check if already revised
    //         if ($discardedProduct->revised == 1) {
    //             return response()->json([
    //                 'success' => false,
    //                 'message' => 'This product is already marked as revised',
    //             ], 400);
    //         }

    //         $revisedQty = $request->input('revised_qty', 0);

    //         // Validate revised quantity
    //         if ($revisedQty > $discardedProduct->qty) {
    //             return response()->json([
    //                 'success' => false,
    //                 'message' => "Revised quantity cannot exceed discarded quantity ({$discardedProduct->qty})",
    //             ], 400);
    //         }

    //         // If some quantity is recovered, add it back to stock
    //         if ($revisedQty > 0) {
    //             $stock = Stock::where('product_id', $discardedProduct->product_id)->first();
                
    //             if ($stock) {
    //                 // Reduce out_stock by revised quantity (returning to inventory)
    //                 $stock->out_stock -= $revisedQty;
    //                 $stock->updated_at = now();
    //                 $stock->save();
    //             }
    //         }

    //         // Mark as revised
    //         $discardedProduct->revised = 1;
    //         $discardedProduct->updated_at = now();
    //         $discardedProduct->save();

    //         DB::commit();

    //         // Load product relation for response
    //         $discardedProduct->load('product:id,name,model,image');

    //         return response()->json([
    //             'success' => true,
    //             'message' => $revisedQty > 0 
    //                 ? "Product revised successfully. {$revisedQty} units returned to stock"
    //                 : 'Product marked as revised successfully',
    //             'data' => $discardedProduct,
    //         ], 200);

    //     } catch (Exception $e) {
    //         DB::rollBack();
            
    //         Log::error('Error revising discarded product: ' . $e->getMessage(), [
    //             'file' => $e->getFile(),
    //             'line' => $e->getLine(),
    //             'request' => $request->all(),
    //         ]);

    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Failed to revise product',
    //             'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
    //         ], 500);
    //     }
    // }

    public function destroy(Request $request)
    {
        // Validation
        $validator = Validator::make($request->all(), [
            'id' => 'required|integer|exists:discarded_products,id',
        ], [
            'id.required' => 'Discarded product ID is required',
            'id.exists' => 'Discarded product not found',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        DB::beginTransaction();

        try {
            $discardedProduct = DiscardedProduct::findOrFail($request->id);

            // Check if product is revised
            if ($discardedProduct->revised == 1) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete a revised discarded product',
                ], 400);
            }

            // Revert stock changes
            $stock = Stock::where('product_id', $discardedProduct->product_id)->first();
            
            if ($stock) {
                $stock->out_stock -= $discardedProduct->qty;
                $stock->updated_at = now();
                $stock->save();
            }

            // Soft delete or hard delete based on your preference
            // For hard delete:
            $discardedProduct->delete();
            
            // For soft delete (if you add SoftDeletes trait):
            // $discardedProduct->deleted_at = now();
            // $discardedProduct->save();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Discarded product deleted successfully',
            ], 200);

        } catch (Exception $e) {
            DB::rollBack();
            
            Log::error('Error deleting discarded product: ' . $e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'request' => $request->all(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete discarded product',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    
    // public function getStatistics()
    // {
    //     try {
    //         $totalDiscarded = DiscardedProduct::sum('qty');
    //         $totalRevised = DiscardedProduct::where('revised', 1)->sum('qty');
    //         $pendingRevision = DiscardedProduct::where('revised', 0)->sum('qty');
    //         $uniqueProducts = DiscardedProduct::distinct('product_id')->count();

    //         return response()->json([
    //             'success' => true,
    //             'message' => 'Statistics retrieved successfully',
    //             'data' => [
    //                 'total_discarded' => $totalDiscarded,
    //                 'total_revised' => $totalRevised,
    //                 'pending_revision' => $pendingRevision,
    //                 'unique_products' => $uniqueProducts,
    //             ],
    //         ], 200);

    //     } catch (Exception $e) {
    //         Log::error('Error fetching statistics: ' . $e->getMessage());

    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Failed to retrieve statistics',
    //             'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
    //         ], 500);
    //     }
    // }

    // public function getProductStock($productId)
    // {
    //     try {
    //         $stock = Stock::where('product_id', $productId)->first();

    //         if (!$stock) {
    //             return response()->json([
    //                 'success' => false,
    //                 'message' => 'No stock found for this product',
    //             ], 404);
    //         }

    //         $availableStock = $stock->in_stock - $stock->out_stock;

    //         return response()->json([
    //             'success' => true,
    //             'message' => 'Stock retrieved successfully',
    //             'data' => [
    //                 'in_stock' => $stock->in_stock,
    //                 'out_stock' => $stock->out_stock,
    //                 'available_stock' => $availableStock,
    //             ],
    //         ], 200);

    //     } catch (Exception $e) {
    //         Log::error('Error fetching product stock: ' . $e->getMessage());

    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Failed to retrieve stock',
    //             'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
    //         ], 500);
    //     }
    // }


    public function bulkDestroy(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'ids' => 'required|array|min:1',
            'ids.*' => 'required|integer|exists:discarded_products,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        DB::beginTransaction();

        try {
            $discardedProducts = DiscardedProduct::whereIn('id', $request->ids)->get();

            // Check if any are revised
            $revisedCount = $discardedProducts->where('revised', 1)->count();
            
            if ($revisedCount > 0) {
                return response()->json([
                    'success' => false,
                    'message' => "Cannot delete. {$revisedCount} product(s) are already revised",
                ], 400);
            }

            foreach ($discardedProducts as $discardedProduct) {
                // Revert stock
                $stock = Stock::where('product_id', $discardedProduct->product_id)->first();
                if ($stock) {
                    $stock->out_stock -= $discardedProduct->qty;
                    $stock->updated_at = now();
                    $stock->save();
                }

                $discardedProduct->delete();
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => count($request->ids) . ' discarded product(s) deleted successfully',
            ], 200);

        } catch (Exception $e) {
            DB::rollBack();
            
            Log::error('Error bulk deleting discarded products: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete discarded products',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }
}