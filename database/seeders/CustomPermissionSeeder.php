<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class CustomPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissionName = [
            'view_all_reports',
            'export_reports',
            'view_all_users',
            'view_tc_no',
            'view_all_managers',
            'view_all_staff',
            'export_staff',
        ];

        foreach ($permissionName as $name) {
            \Spatie\Permission\Models\Permission::firstOrCreate(['name' => $name]);
        }
    }


}
