<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use App\Http\Controllers\Controller;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class PermissionController extends Controller
{
    
    public function getData()
    {
        return Permission::orderBy('module','asc')->get();
    }
    
    public function getDataByModule()
    {
        try {
            $permissions = Permission::all()->groupBy('module');
    
            return response()->json([
                'status' => true,
                'data' => $permissions
            ], 200);
    
        } catch (\Exception $e) {
    
            return response()->json([
                'status' => false,
                'message' => 'Something went wrong',
                'error' => $e->getMessage()
            ], 500);
        }
    }


   public function store(Request $request)
    {
        // Validate request
        $validator = Validator::make($request->all(), [
            'module'       => ['required', 'string', 'max:255'],
            'action_type'  => ['required', Rule::in(['crud', 'other'])],
            'custom_action' => [
                Rule::requiredIf(fn () => $request->action_type === 'other'),
                'nullable',
                'string',
                'min:1',
                'max:50',
                // allow letters, numbers, spaces, dots, hyphens, underscores
                'regex:/^[a-zA-Z0-9\s._-]+$/',
            ],
            // optional: permission from frontend (preview text) – we don't use it
            'permission'   => ['sometimes', 'string'],
        ], [
            'module.required'        => 'Module is required.',
            'module.max'             => 'Module is too long.',
            'action_type.required'   => 'Action type is required.',
            'action_type.in'         => 'Action type must be either "crud" or "other".',
            'custom_action.required' => 'Custom action is required when action type is other.',
            'custom_action.regex'    => 'Custom action can only contain letters, numbers, spaces, dots, hyphens and underscores.',
        ]);
    
        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed.',
                'errors'  => $validator->errors(),
            ], 422);
        }
    
        $data = $validator->validated();
    
        // Normalize module
        $module = trim($data['module']);
    
        // Helper to normalize custom action: "Approve Order" -> "approve_order"
        $normalizeAction = function (?string $action) {
            if ($action === null) {
                return null;
            }
    
            $a = trim($action);
            $a = mb_strtolower($a, 'UTF-8');
            $a = preg_replace('/\s+/', '_', $a); // spaces -> _
            $a = trim($a, '_');                  // remove extra _ at edges
    
            return $a;
        };
    
        DB::beginTransaction();
    
        try {
            $created = [];
            $skipped = [];
    
            if ($data['action_type'] === 'crud') {
                // We always generate these 4: module.create/read/update/delete
                $methods = ['create', 'read', 'update', 'delete'];
    
                foreach ($methods as $method) {
                    $name = "{$module}.{$method}";
    
                    // skip if already exists for the same guard
                    $exists = Permission::where('name', $name)
                        ->where('guard_name', 'api')
                        ->exists();
    
                    if ($exists) {
                        $skipped[] = $name;
                        continue;
                    }
    
                    $permission = Permission::create([
                        'name'       => $name,
                        'guard_name' => 'api',
                        'module'     => $module, // keep if you have 'module' column
                    ]);
    
                    $created[] = $permission;
                }
            } else {
                // other (custom) action
                $safeAction = $normalizeAction($data['custom_action']);
                $name = "{$module}.{$safeAction}";
    
                $exists = Permission::where('name', $name)
                    ->where('guard_name', 'api')
                    ->exists();
    
                if ($exists) {
                    $skipped[] = $name;
                } else {
                    $permission = Permission::create([
                        'name'       => $name,
                        'guard_name' => 'api',
                        'module'     => $module,
                    ]);
    
                    $created[] = $permission;
                }
            }
    
            DB::commit();
    
            $status = count($created) ? 201 : 200;
    
            return response()->json([
                'message'       => count($created) ? 'Permissions processed.' : 'No new permissions created.',
                'created_count' => count($created),
                'skipped_count' => count($skipped),
                'created'       => $created,
                'skipped'       => $skipped,
            ], $status);
        } catch (\Throwable $e) {
            DB::rollBack();
    
            return response()->json([
                'message' => 'Failed to create permissions.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    public function update(Request $request, Permission $permission)
    {
        $request->validate([
            'name' => 'required|string|unique:permissions,name,' . $permission->id,
        ]);

        $permission->update([
            'name' => $request->name,
        ]);

        return response()->json($permission);
    }

    public function destroy($id)
    {
        $permission = Permission::find($id);
    
        if (!$permission) {
            return response()->json([
                'message' => 'Permission not found'
            ], 404);
        }
    
        // Remove permission from all roles
        $permission->syncRoles([]);
    
        // Remove permission from all users (model_has_permissions)
        DB::table('model_has_permissions')
            ->where('permission_id', $permission->id)
            ->delete();
    
        // Delete permission
        $permission->delete();
    
        return response()->json([
            'message' => 'Permission deleted successfully'
        ], 200);
    }
    
    public function getModulePermission(Request $request)
    {
        try {
            $request->validate([
                'id' => 'required|integer|exists:roles,id',
            ]);
    
            $role = Role::with('permissions')->find($request->id);
    
            
    
            return response()->json([
                'status' => true,
                'message' => 'Permissions fetched successfully',
                'data' => $role
            ], 200);
    
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'status' => false,
                'message' => 'Role not found'
            ], 404);
    
        } catch (Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to fetch permissions',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
