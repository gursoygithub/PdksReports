<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ReportService
{
    /**
     * Günlük rapor.
     */
    public function reportsDailyService(): int
    {
        return $this->processReports(
            'http://10.10.50.6:8080/api/zk/cardreadingsdaily',
            60
        );
    }

    /**
     * 90 günlük rapor.
     */
    public function reportsLast90DaysService(): int
    {
        return $this->processReports(
            'http://10.10.50.6:8080/api/zk/cardreadingsninedays',
            120
        );
    }

    /**
     * Ortak işlem metodu.
     */
    protected function processReports(string $apiUrl, int $timeout): int
    {
        $batchSize = 1000;
        $buffer = [];
        $totalProcessed = 0;

        try {
            Log::info("Rapor çekiliyor: {$apiUrl}");

            $response = Http::timeout($timeout)->get($apiUrl);

            if ($response->failed()) {
                Log::error('API hatası', [
                    'url' => $apiUrl,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                throw new \Exception("API isteği başarısız oldu. HTTP Kod: " . $response->status());
            }

            $dataList = $response->json();

            foreach ($dataList as $data) {

                // TC boşsa hiç ekleme
                if (empty($data['tc_no'])) {
                    continue;
                }

                $formatted = $this->formatData($data);

                if ($formatted !== null) {
                    $buffer[] = $formatted;
                }

                if (count($buffer) >= $batchSize) {
                    $this->handleBatch($buffer);
                    $totalProcessed += count($buffer);
                    $buffer = [];
                }
            }

            if (!empty($buffer)) {
                $this->handleBatch($buffer);
                $totalProcessed += count($buffer);
            }

            Log::info("Rapor tamamlandı. Toplam kayıt: {$totalProcessed}");
            return $totalProcessed;

        } catch (\Throwable $e) {
            Log::error('Rapor hatası: ' . $e->getMessage(), [
                'exception' => $e
            ]);
            throw $e;
        }
    }

    /**
     * Batch işle: employee eşleme + upsert.
     */
    protected function handleBatch(array $buffer): void
    {
        // Tüm TC’leri al
        $tcList = array_column($buffer, 'tc_no');

        // Çalışanları tek seferde çek (performanslı)
        $employees = DB::table('employees')
            ->whereIn('tc_no', $tcList)
            ->pluck('id', 'tc_no')
            ->toArray();

        // employee_id’leri buffer’a ekle
        foreach ($buffer as &$row) {
            $row['employee_id'] = $employees[$row['tc_no']] ?? null;
        }

        $this->upsertReports($buffer);
    }

    /**
     * Kayıt formatlama.
     */
    protected function formatData(array $data): ?array
    {
        if (empty($data['tc_no'])) {
            return null;
        }

        return [
            'external_id'     => $data['external_id'] ?? null,
            'tc_no'           => $data['tc_no'],
            'full_name'       => $data['full_name'] ?? null,
            'department_name' => $data['department_name'] ?? null,
            'position_name'   => $data['position_name'] ?? null,
            'date'            => $data['date'] ?? null,
            'day'             => $data['day'] ?? null,
            'first_reading'   => self::parseDate($data['first_reading'] ?? null),
            'last_reading'    => self::parseDate($data['last_reading'] ?? null),
            'working_time'    => $data['working_time'] ?? null,
            'status'          => $data['status'] ?? null,
            'created_at'      => now(),
            'updated_at'      => now(),
        ];
    }

    /**
     * Tarih formatlama.
     */
    protected static function parseDate(?string $value): ?string
    {
        if (empty($value) || $value === '?') {
            return null;
        }

        try {
            return Carbon::parse($value)->format('Y-m-d H:i:s');
        } catch (\Exception $e) {
            if (preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}\.\d+$/', $value)) {
                try {
                    return Carbon::createFromFormat('Y-m-d H:i:s.u', $value)
                        ->format('Y-m-d H:i:s');
                } catch (\Exception $ex) {
                    Log::warning('Tarih dönüştürme hatası (milisaniyeli)', ['value' => $value]);
                    return null;
                }
            }

            Log::warning('Tarih dönüştürme hatası', ['value' => $value]);
            return null;
        }
    }

    /**
     * Upsert işlemi.
     */
    protected function upsertReports(array $buffer): void
    {
        DB::table('reports')->upsert(
            $buffer,
            ['external_id'], // eşsiz alan
            [
                'employee_id',
                'tc_no',
                'full_name',
                'department_name',
                'position_name',
                'date',
                'day',
                'first_reading',
                'last_reading',
                'working_time',
                'status',
                'updated_at',
            ]
        );
    }
}