<?php

namespace App\Http\Controllers\Admin\Modules\Master;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Category;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;


class CategoryController extends Controller
{
    public function getData(Request $request)
    {
        try{
            $categories = Category::orderBy('id','desc')->paginate(10);
            return response()->json($categories);
        }catch(\Exception $e){
            return response()->json(['error' => 'Failed to fetch Categorys'], 500);
        }
        
    }

    public function search(Request $request)
    {
        try{
            $query = Category::orderBy('id','desc');
            if ($request->has('name') && !empty($request->name)) {
                $query->where('name', 'like', '%' . $request->name . '%');
            }
            if ($request->has('status') && $request->status !== null) {
                $query->where('status', $request->status);
            }
            $categories = $query->paginate(10);
            return response()->json($categories);
        }catch(\Exception $e){
            return response()->json(['error' => 'Failed to fetch categorys'], 500);
        }
        
    }

    public function store(Request $request)
    {
        try{
            $request->validate([
                'name' => [
                    'required',
                    'string',
                    'max:255',
                    Rule::unique('categories', 'name')->whereNull('deleted_at'),
                ],
            ]);

            $category = new Category();
            $category->name = $request->name;
            $category->group_id = $request->group_id;
            $category->created_by = auth()->user()->id;
            $category->status = $request->status ?? 0;
            $category->save();
            return response()->json(['message' => 'Category created successfully',
                'data' => $category]);
        }catch(\Exception $e){
            return response()->json(['error' => 'Failed to fetch category', $e->getMessage()], 500);
        }
        
    }

    public function edit(Request $request, $id)
    {
        try{

            $category =Category::find($id);

            if(!$category){
                return response()->json(['error' => 'Category not found'], 404);
            }
            return response()->json(['message' => 'Category fetch  successfully',
                'data' => $category]);
        }catch(\Exception $e){
            return response()->json(['error' => 'Failed to fetch Category', $e->getMessage()], 500);
        }
        
    }

    public function update(Request $request, $id){
        try{

            $request->validate([
                'name' => [
                    'required',
                    'string',
                    'max:255',
                    Rule::unique('categories', 'name')
                        ->ignore($id) 
                        ->whereNull('deleted_at'), 
                ],
                'status' => 'nullable|in:0,1',
            ]);

            $category =Category::find($id);

            if(!$category){
                return response()->json(['error' => 'Category not found'], 404);
            }

            $category->name = $request->name;
            $category->created_by = auth()->user()->id;
            $category->status = $request->status ?? $category->status;
            $category->save();

            return response()->json(['message' => 'Category updated  successfully',
                'data' => $category]);
        }catch(\Exception $e){
            return response()->json(['error' => 'Failed to fetch Category', $e->getMessage()], 500);
        }
        
    }

    public function delete(Request $request, $id){
        try{

            $category =Category::find($id);

            if(!$category){
                return response()->json(['error' => 'Category not found'], 404);
            }
            $category->delete();

            return response()->json(['message' => 'Category deleted  successfully']);
        }catch(\Exception $e){
            return response()->json(['error' => 'Failed to fetch Category', $e->getMessage()], 500);
        }
        
    }

    public function statusUpdate(Request $request)
    {
        try{
            $id = $request->id;
            $category =Category::find($id);

            if(!$category){
                return response()->json(['error' => 'Category not found'], 404);
            }
            $category->status= !$category->status;
            $category->save();

            return response()->json(['message' => 'Category status updated  successfully']);
        }catch(\Exception $e){
            return response()->json(['error' => 'Failed to fetch  category', $e->getMessage()], 500);
        }
        
    }
}
