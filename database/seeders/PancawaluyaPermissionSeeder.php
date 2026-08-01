<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PancawaluyaPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissions = [
            'pancawaluya.reward-category.view',
            'pancawaluya.reward-category.create',
            'pancawaluya.reward-category.update',
            'pancawaluya.reward-category.delete',
            'pancawaluya.reward-category.restore',
            'pancawaluya.reward-category.force-delete',

            'pancawaluya.reward.view',
            'pancawaluya.reward.create',
            'pancawaluya.reward.update',
            'pancawaluya.reward.delete',
            'pancawaluya.reward.restore',
            'pancawaluya.reward.force-delete',
            'pancawaluya.reward.approve',

            'pancawaluya.violation-category.view',
            'pancawaluya.violation-category.create',
            'pancawaluya.violation-category.update',
            'pancawaluya.violation-category.delete',
            'pancawaluya.violation-category.restore',
            'pancawaluya.violation-category.force-delete',

            'pancawaluya.violation.view',
            'pancawaluya.violation.create',
            'pancawaluya.violation.update',
            'pancawaluya.violation.delete',
            'pancawaluya.violation.restore',
            'pancawaluya.violation.force-delete',
            'pancawaluya.violation.approve',

            'pancawaluya.mapping.manage',
            'pancawaluya.sp.generate',
        ];

        foreach ($permissions as $name) {
            Permission::firstOrCreate([
                'name' => $name,
                'guard_name' => 'web',
            ]);
        }

        $admin = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $admin->givePermissionTo($permissions);

        $readPermissions = [
            'pancawaluya.reward-category.view',
            'pancawaluya.reward.view',
            'pancawaluya.violation-category.view',
            'pancawaluya.violation.view',
        ];

        foreach (['guru', 'wali_kelas', 'bk', 'kesiswaan'] as $roleName) {
            $role = Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
            $role->syncPermissions($readPermissions);
        }

        $student = Role::firstOrCreate(['name' => 'siswa', 'guard_name' => 'web']);
        $student->syncPermissions([]);
    }
}
