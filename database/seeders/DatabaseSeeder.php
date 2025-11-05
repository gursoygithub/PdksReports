<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            CustomPermissionSeeder::class,
            InitSeeder::class,
        ]);

        // 2️⃣ Stream ve Daily komutlarını çağır
        try {
            $this->command->info('📡 Rapor yükleme başlatılıyor (stream)...');
            Artisan::call('report:stream');
            $this->command->info(Artisan::output());

            $this->command->info('📅 Günlük rapor yükleme başlatılıyor...');
            Artisan::call('report:daily');
            $this->command->info(Artisan::output());

            $this->command->info('✅ Stream ve daily rapor yüklemeleri tamamlandı.');

        } catch (\Throwable $e) {
            $this->command->error('❌ Rapor yükleme sırasında hata oluştu: ' . $e->getMessage());
            Log::error('Seeder rapor hatası', ['exception' => $e]);
        }
    }
}
