<?php
namespace App\Http\Controllers\Admin\Modules;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Stock;
use Illuminate\Support\Facades\DB;

class StockController extends Controller
{
    public function getData(Request $request)
    {
        try {
            
            $validated = $request->validate([
                'per_page' => 'nullable|integer|min:1|max:100',
                'page' => 'nullable|integer|min:1',
                'search' => 'nullable|string|max:255',
            ]);

            $perPage = $validated['per_page'] ?? 10;
            $search = isset($validated['search']) ? trim($validated['search']) : null;
            
            
            $query = Stock::with(['product:id,name'])
                ->select('id', 'product_id', 'in_stock', 'out_stock', 'available_qty', 'created_at')
                ->orderByDesc('id');
            
            
            if ($search !== null && $search !== '') {
                $query->where(function($q) use ($search) {
                    
                    $q->where('id', 'like', "%{$search}%")
                     
                      ->orWhere('in_stock', 'like', "%{$search}%")
                      ->orWhere('out_stock', 'like', "%{$search}%")
                      ->orWhere('available_qty', 'like', "%{$search}%")
                      // Search by product name
                      ->orWhereHas('product', function($productQuery) use ($search) {
                          $productQuery->where('name', 'ILIKE', "%{$search}%");
                      });
                });
            }
            
            // Paginate results
            $items = $query->paginate($perPage);
            
            return response()->json([
                "data" => $items->items(),
                "current_page" => $items->currentPage(),
                "last_page" => $items->lastPage(),
                "per_page" => $items->perPage(),
                "total" => $items->total(),
                "from" => $items->firstItem(),
                "to" => $items->lastItem(),
            ], 200);
    
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'error' => 'Validation failed',
                'message' => $e->getMessage(),
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            \Log::error('Stock getData error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'error' => 'Failed to fetch stock items',
                'message' => config('app.debug') ? $e->getMessage() : 'An error occurred',
            ], 500);
        }
    }
}