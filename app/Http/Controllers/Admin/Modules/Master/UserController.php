<?php

namespace App\Http\Controllers\Admin\Modules\Master;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\UserType;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Hash;
use Spatie\Permission\Models\Role;


class UserController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api');

        $this->middleware('permission:users.read')->only([
            'getUser'
        ]);

        $this->middleware('permission:users.create')->only([
            'store'
        ]);

        $this->middleware('permission:users.update')->only([
            'edit', 'update', 'statusUpdate'
        ]);

        $this->middleware('permission:users.delete')->only([
            'delete'
        ]);
    }
    
    public function getUser(Request $request)
    {
        
        try{
            $users = User::with('roles')->orderBy('id','desc')->get();
            $arr = ['data' =>$users];
            return response()->json($arr);
        }catch(\Exception $e){
            return response()->json(['error' => 'Failed to fetch user'], 500);
        }
        
    }
    
    public function search(Request $request)
    {
        try {
            $search = $request->input('search', '');
            $limit = $request->input('limit', 10);
            $page = $request->input('page', 1);


            $query = User::with('roles')
                ->select('users.*');

            // Apply search filter if search term exists
            if (!empty($search)) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'ILIKE', "%{$search}%")
                      ->orWhere('email', 'ILIKE', "%{$search}%")
                      ->orWhere('mobile', 'ILIKE', "%{$search}%")
                      ->orWhere('city', 'ILIKE', "%{$search}%")
                      ->orWhere('address', 'ILIKE', "%{$search}%")
                      ->orWhereHas('roles', function ($roleQuery) use ($search) {
                          $roleQuery->where('name', 'ILIKE', "%{$search}%");
                      });
                });
            }

            // Order by latest first
            $query->orderBy('id', 'desc');

            // Paginate results
            $users = $query->paginate($limit, ['*'], 'page', $page);

            // Return standardized response
            return response()->json([
                'data' => $users->items(),
                'total' => $users->total(),
                'current_page' => $users->currentPage(),
                'per_page' => $users->perPage(),
                'last_page' => $users->lastPage(),
                'from' => $users->firstItem(),
                'to' => $users->lastItem(),
            ], 200);

        } catch (\Exception $e) {
            Log::error('User search failed: ' . $e->getMessage());
            
            return response()->json([
                'error' => 'Failed to fetch users',
                'message' => config('app.debug') ? $e->getMessage() : 'An error occurred'
            ], 500);
        }
    }

    // public function search(Request $request)
    // {
    //     try {
    //         $search = $request->input('search');
    
    //         $users = User::with('roles')
    //             ->when($search, function ($query) use ($search) {
    //                 $query->where(function ($q) use ($search) {
    //                     $q->where('name', 'ILIKE', "%{$search}%")
    //                       ->orWhere('email', 'ILIKE', "%{$search}%")
    //                       ->orWhere('mobile', 'ILIKE', "%{$search}%")
    //                       ->orWhere('city', 'ILIKE', "%{$search}%")
    //                       ->orWhereHas('userType', function ($ut) use ($search) {
    //                           $ut->where('name', 'ILIKE', "%{$search}%");
    //                       });
    //                 });
    //             })
    //             ->orderBy('id', 'desc')
    //             ->paginate($request->input('limit', 10));
    
    //         return response()->json($users);
    
    //     } catch (\Exception $e) {
    //         return response()->json(['error' => 'Failed to fetch user'], 500);
    //     }
    // }
    
    
    public function getSupervisor(Request $request)
    {
        
        try{
            $users = User::with('roles')
            ->whereHas('roles', function ($q) {
                $q->where('name', 'supervisor');
            })
            ->orderBy('id', 'desc')
            ->get();
            $arr = ['data' =>$users];
            return response()->json($arr);
        }catch(\Exception $e){
            return response()->json(['error' => 'Failed to fetch user'], 500);
        }
        
    }

    public function store(Request $request)
    {
        try{
            
           $request->validate(
            [
                'email' => [
                    'required',
                    'string',
                    'max:255',
                    Rule::unique('users', 'email')->whereNull('deleted_at'),
                ],
            ],
            [
                'email.required' => 'Email address is required.',
                'email.string'   => 'Email address must be a valid string.',
                'email.max'      => 'Email address must not exceed 255 characters.',
                'email.unique'   => 'This email address is already in use.',
            ]
        );



            $user = new User();

            $user->name = $request->name;
            $user->email = $request->email;
            $user->mobile = $request->mobile;
            $user->state_id = $request->state_id;
            $user->city = $request->city;
            $user->address = $request->address;
            $user->zip_code = $request->zip_code;
            $user->password = Hash::make($request->password);
           if($request->has('image')){
                $image = $request->file('image');
                $randomName = rand(10000000, 99999999);
                $imageName = time().'_'.$randomName . '.' . $image->getClientOriginalExtension();
                $image->move(public_path('uploads/users/'), $imageName);
                $user->image = '/uploads/users/'.$imageName;

            }
            $user->created_by = auth()->user()->id;
            $user->status = $request->status ?? 1;
            $user->save();
            $user->syncRoles([$request->user_type_id]); 
            // $role = Role::findById($request->user_type_id, 'api');
            // $user->syncRoles([$role->name]);

            // reload relationship
            $user->load('roles');
            return response()->json(['message' => 'User created successfully',
                'data' => $user]);
        }catch(\Exception $e){
            return response()->json(['error' => 'Failed to fetch user', $e->getMessage()], 500);
        }
        
    }

    public function edit(Request $request, $id)
    {
        try{
            $user =User::find($id);

            if(!$user){
                return response()->json(['error' => 'User  not found'], 404);
            }
            return response()->json(['message' => 'User  fetch  successfully',
                'data' => $user]);
        }catch(\Exception $e){
            return response()->json(['error' => 'Failed to fetch user', $e->getMessage()], 500);
        }
        
    }

    public function update(Request $request, $id)
    {
        try{
            $request->validate([
                'email' => [
                    'required',
                    'max:255',
                    Rule::unique('users', 'email')
                        ->ignore($id) 
                        ->whereNull('deleted_at'), 
                ],
                'status' => 'nullable|in:0,1',
            ]);

            $user =User::find($id);

            if(!$user){
                return response()->json(['error' => 'User not found'], 404);
            }
            $user->name = $request->name;
            $user->email = $request->email;
            $user->mobile = $request->mobile;
            $user->state_id = $request->state_id;
            $user->city = $request->city;
            $user->zip_code = $request->zip_code;
            $user->address = $request->address;
            // $user->user_type_id = $request->user_type_id;
           if($request->has('image')){
                $image = $request->file('image');
                $randomName = rand(10000000, 99999999);
                $imageName = time().'_'.$randomName . '.' . $image->getClientOriginalExtension();
                $image->move(public_path('uploads/users/'), $imageName);
                $user->image = '/uploads/users/'.$imageName;

            }
            $user->status = $request->status ?? $user->status;
            $user->save();
            
            // $user->syncRoles([$request->user_type_id]);
            $role = Role::findById($request->user_type_id, 'api');
            $user->syncRoles([$role->name]);

            // reload relationship
            $user->load('roles');

            return response()->json(['message' => 'User updated  successfully',
                'data' => $user]);
        }catch(\Exception $e){
            return response()->json(['error' => 'Failed to fetch user', $e->getMessage()], 500);
        }
        
    }

    public function delete(Request $request, $id)
    {
        try{
            $user =User::find($id);

            if(!$user){
                return response()->json(['error' => 'User not found'], 404);
            }
            $user->delete();

            return response()->json(['message' => 'User deleted  successfully']);
        }catch(\Exception $e){
            return response()->json(['error' => 'Failed to fetch user', $e->getMessage()], 500);
        }
        
    }
    public function statusUpdate(Request $request)
    {
        try{
            $id = $request->id;
            $user =User::find($id);

            if(!$user){
                return response()->json(['error' => 'User not found'], 404);
            }
            $user->status= $request->status ?? $user->status;
            $user->save();

            return response()->json(['message' => 'User status updated  successfully']);
        }catch(\Exception $e){
            return response()->json(['error' => 'Failed to fetch user', $e->getMessage()], 500);
        }
        
    }
}