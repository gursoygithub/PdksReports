<?php

namespace Database\Seeders;

use App\Services\ReportService;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class UploadReports extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        try {
            DB::table('reports')->truncate();

            $service = new ReportService();

            $this->command->info('Personel giriş-çıkış verileri yükleniyor...');

            $processedCount = $service->reportsService();

            $this->command->info("Toplam {$processedCount} kayıt işlendi.");
        } catch (\Exception $e) {
            $this->command->error('Hata: ' . $e->getMessage());
        }
    }
}
