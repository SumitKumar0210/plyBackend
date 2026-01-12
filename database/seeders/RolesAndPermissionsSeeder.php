<?php
// database/seeders/RolesAndPermissionsSeeder.php
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run()
    {
        // reset cached roles & permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // create permissions
        $permissions = [
            'manage users',
            'create reports',
            'view reports',
            'manage inventory',
            'assign roles',
            // add more domain-specific permissions
        ];

        foreach ($permissions as $perm) {
            Permission::firstOrCreate(['name' => $perm]);
        }

        // create roles and assign permissions
        $admin = Role::firstOrCreate(['name' => 'admin']);
        $admin->givePermissionTo(Permission::all());

        $manager = Role::firstOrCreate(['name' => 'manager']);
        $manager->givePermissionTo(['view reports', 'create reports', 'manage inventory']);

        $staff = Role::firstOrCreate(['name' => 'staff']);
        $staff->givePermissionTo(['view reports']);
    }
}
