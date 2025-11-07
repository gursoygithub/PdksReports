<?php

namespace App\Services;

use App\Models\Employee;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class EmployeeService
{
    public function employeeService()
    {
        $response = Http::get('http://10.10.50.6:8080/api/zk/personnel_employee');

        if (!$response->successful()) {
            throw new \Exception("API isteği başarısız oldu: " . $response->status());
        }

        $employees = $response->json();

        // JSON beklenmedik yapıda mı kontrol et
        if (!is_array($employees)) {
            throw new \Exception('API verisi geçersiz veya boş geldi.');
        }

        $count = 0;

        foreach ($employees as $data) {
            $tcNo = $data['nickname'] ?? null;

            // Boş veya geçersiz nickname (tc_no yerine kullanılan) kayıtları atla
            if (empty($tcNo) || strlen($tcNo) < 3) {
                continue;
            }

            $employee = Employee::updateOrCreate(
                ['tc_no' => $tcNo],
                [
                    'first_name' => $data['first_name'] ?? null,
                    'last_name' => $data['last_name'] ?? null,
                    'status' => $data['status'] ?? 100,
                    'create_time' => $this->toMysqlDate($data['create_time'] ?? null),
                    'update_time' => $this->toMysqlDate($data['update_time'] ?? null),
                ]
            );

            // Sadece yeni eklenen kayıtları say
            if ($employee->wasRecentlyCreated) {
                $count++;
            }
        }

        return $count;
    }

    private function toMysqlDate(?string $date)
    {
        if (!$date) return null;

        try {
            return Carbon::parse($date)->format('Y-m-d H:i:s');
        } catch (\Exception $e) {
            return null;
        }
    }
}
