<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use App\Enums\ActiveStatusEnum;

class InitSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $checkUserTable = User::count();
        if ($checkUserTable == 0) {
            User::create([
                'name' => 'Admin',
                'email' => 'sa@app.com',
                'password' => 'password',
                'status' => ActiveStatusEnum::ACTIVE,
                'created_by' => 1,
            ]);
        }
    }
}
