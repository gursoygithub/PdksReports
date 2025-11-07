<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class EmployeeCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'employee:daily';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Günlük personel verilerini API üzerinden çekip veritabanına kaydeder';

    /**
     * Execute the console command.
     */
    public function handle(\App\Services\EmployeeService $employeeService): int
    {
        $this->info('📅 Günlük personel senkronizasyonu başlatılıyor...');

        try {
            $count = $employeeService->employeeService();
            $this->info("✅ Günlük personel tamamlandı. Toplam {$count} kayıt işlendi.");
            return Command::SUCCESS;
        } catch (\Throwable $e) {
            $this->error('❌ Hata: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
