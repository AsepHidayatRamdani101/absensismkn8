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

            'pancawaluya.transaction.reward.view',
            'pancawaluya.transaction.reward.create',
            'pancawaluya.transaction.reward.update',
            'pancawaluya.transaction.reward.delete',
            'pancawaluya.transaction.reward.restore',
            'pancawaluya.transaction.reward.force-delete',
            'pancawaluya.transaction.reward.approve',
            'pancawaluya.transaction.reward.validate',
            'pancawaluya.transaction.reward.export',
            'pancawaluya.transaction.reward.import',

            'pancawaluya.transaction.violation.view',
            'pancawaluya.transaction.violation.create',
            'pancawaluya.transaction.violation.update',
            'pancawaluya.transaction.violation.delete',
            'pancawaluya.transaction.violation.restore',
            'pancawaluya.transaction.violation.force-delete',
            'pancawaluya.transaction.violation.approve',
            'pancawaluya.transaction.violation.validate',
            'pancawaluya.transaction.violation.export',
            'pancawaluya.transaction.violation.import',

            'pancawaluya.transaction.history.view',
            'pancawaluya.transaction.history.export',
        ];

        foreach ($permissions as $name) {
            Permission::firstOrCreate([
                'name' => $name,
                'guard_name' => 'web',
            ]);
        }

        $admin = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $admin->givePermissionTo($permissions);

        $masterReadPermissions = [
            'pancawaluya.reward-category.view',
            'pancawaluya.reward.view',
            'pancawaluya.violation-category.view',
            'pancawaluya.violation.view',
        ];

        $guru = Role::firstOrCreate(['name' => 'guru', 'guard_name' => 'web']);
        $guru->syncPermissions(array_merge($masterReadPermissions, [
            'pancawaluya.transaction.reward.view',
            'pancawaluya.transaction.reward.create',
            'pancawaluya.transaction.reward.update',
        ]));

        $waliKelas = Role::firstOrCreate(['name' => 'wali_kelas', 'guard_name' => 'web']);
        $waliKelas->syncPermissions(array_merge($masterReadPermissions, [
            'pancawaluya.transaction.reward.view',
            'pancawaluya.transaction.reward.create',
            'pancawaluya.transaction.reward.update',
            'pancawaluya.transaction.violation.view',
            'pancawaluya.transaction.violation.create',
            'pancawaluya.transaction.violation.update',
            'pancawaluya.transaction.history.view',
        ]));

        $kesiswaan = Role::firstOrCreate(['name' => 'kesiswaan', 'guard_name' => 'web']);
        $kesiswaan->syncPermissions(array_merge($masterReadPermissions, [
            'pancawaluya.transaction.reward.view',
            'pancawaluya.transaction.reward.create',
            'pancawaluya.transaction.reward.update',
            'pancawaluya.transaction.reward.delete',
            'pancawaluya.transaction.reward.validate',
            'pancawaluya.transaction.reward.export',
            'pancawaluya.transaction.reward.import',
            'pancawaluya.transaction.violation.view',
            'pancawaluya.transaction.violation.create',
            'pancawaluya.transaction.violation.update',
            'pancawaluya.transaction.violation.delete',
            'pancawaluya.transaction.violation.validate',
            'pancawaluya.transaction.violation.export',
            'pancawaluya.transaction.violation.import',
            'pancawaluya.transaction.history.view',
            'pancawaluya.transaction.history.export',
        ]));

        $bk = Role::firstOrCreate(['name' => 'bk', 'guard_name' => 'web']);
        $bk->syncPermissions([
            'pancawaluya.reward-category.view',
            'pancawaluya.reward.view',
            'pancawaluya.violation-category.view',
            'pancawaluya.violation.view',
            'pancawaluya.transaction.violation.view',
            'pancawaluya.transaction.history.view',
        ]);

        $student = Role::firstOrCreate(['name' => 'siswa', 'guard_name' => 'web']);
        $student->syncPermissions([
            'pancawaluya.transaction.history.view',
        ]);
    }
}
