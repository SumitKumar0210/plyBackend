<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Spatie\Permission\Models\Role;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\Rule;
use Exception;
use Spatie\Permission\PermissionRegistrar;


class RoleController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api');

        $this->middleware('permission:roles.read')->only([
            'getData'
        ]);

        $this->middleware('permission:roles.create')->only([
            'store'
        ]);

        $this->middleware('permission:roles.update')->only([
            'update', 'statusUpdate'
        ]);

        $this->middleware('permission:roles.delete')->only([
            'destroy'
        ]);
        
        $this->middleware('permission:roles.assign')->only([
            'assignPermission'
        ]);
    }
    
    /**
     * Display a listing of roles.
     */
    public function getData(Request $request)
    {
        
        try {
          
            $query = Role::orderBy('id','desc');
            if ($request->status) {
                $query->where('status', '1');
            }
            
            
            $roles = $query->get();

            return response()->json([
                'status' => true,
                'data' => $roles
            ], 200);
        } catch (Exception $e) {
            Log::error('Role index failed: '.$e->getMessage(), ['exception' => $e]);

            return response()->json([
                'status' => false,
                'message' => 'Failed to load roles'
            ], 500);
        }
    }

    /**
     * Store a newly created role.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|unique:roles,name',
        ]);

        DB::beginTransaction();
        try {
            $role = Role::create([
                'name' => $request->name,
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'data' => $role
            ], 201);
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Role store failed: '.$e->getMessage(), ['payload' => $request->all(), 'exception' => $e]);

            return response()->json([
                'status' => false,
                'message' => 'Failed to create role'
            ], 500);
        }
    }

    /**
     * Update the specified role.
     *
     * Uses route-model binding for $role.
     */
    public function update(Request $request, $id)
    {
        // Ensure role exists first
        $role = Role::find($id);
        if (! $role) {
            return response()->json([
                'status' => false,
                'message' => 'Role not found'
            ], 404);
        }
    
        
        $request->validate([
            'name' => ['required', 'string', Rule::unique('roles', 'name')->ignore($role->id)],
        ]);
    
        DB::beginTransaction();
        try {
            $role->update([
                'name' => $request->name,
            ]);
    
            DB::commit();
    
            return response()->json([
                'status' => true,
                'data' => $role
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Role update failed: '.$e->getMessage(), [
                'role_id' => $role->id ?? $id,
                'payload' => $request->all(),
                'exception' => $e
            ]);
    
            return response()->json([
                'status' => false,
                'message' => 'Failed to update role'
            ], 500);
        }
    }

    /**
     * Remove the specified role.
     *
     * Uses route-model binding for $role.
     */
    public function destroy(Request $request, $id)
    {
        DB::beginTransaction();
        try {
            $role = Role::find($id);
            $role->delete();

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Role deleted successfully'
            ], 200);
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Role delete failed: '.$e->getMessage(), ['role_id' => $role->id, 'exception' => $e]);

            return response()->json([
                'status' => false,
                'message' => 'Failed to delete role'
            ], 500);
        }
    }

    /**
     * Update the status of a role.
     */
    public function statusUpdate(Request $request)
    {
        $request->validate([
            'id' => 'required|integer|exists:roles,id',
        ]);

        try {
            $role = Role::findOrFail($request->id);
            $role->status = !$role->status;
            $role->save();

            return response()->json([
                'status' => true,
                'message' => 'Role status updated successfully',
                'data' => $role
            ], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'status' => false,
                'message' => 'Role not found'
            ], 404);
        } catch (Exception $e) {
            Log::error('Role status update failed: '.$e->getMessage(), ['payload' => $request->all(), 'exception' => $e]);

            return response()->json([
                'status' => false,
                'message' => 'Failed to update role status'
            ], 500);
        }
    }
    
    
    public function assignPermission(Request $request)
    {
        $request->validate([
            'id' => 'required|integer|exists:roles,id',
            'permissionNames' => 'required|array',
            'permissionNames.*' => 'string|exists:permissions,name',
        ]);
    
        try {
            DB::beginTransaction();
            // auth()->user()->assignRole('staff');
    
            // find role (will throw ModelNotFoundException if not found)
            $role = Role::findOrFail($request->id);
    
            // syncPermissions accepts array of names, ids or Permission objects.
            // This will replace existing permissions with the provided ones.
            $role->syncPermissions($request->permissionNames);
    
            // commit DB changes
            DB::commit();
    
            // IMPORTANT: clear Spatie's permission cache so app sees the new perms
            app(PermissionRegistrar::class)->forgetCachedPermissions();
    
            // Refresh role so relation data is fresh and then load permissions
            $role = $role->fresh()->load('permissions');
    
            return response()->json([
                'status' => true,
                'message' => 'Permissions assigned to role successfully',
                'data' => $role
            ], 200);
    
        } catch (ModelNotFoundException $e) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => 'Role not found'
            ], 404);
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Assign permission failed: '.$e->getMessage(), [
                'payload' => $request->all(),
                'exception' => $e
            ]);
    
            return response()->json([
                'status' => false,
                'message' => 'Failed to assign permissions'
            ], 500);
        }
    }
}
