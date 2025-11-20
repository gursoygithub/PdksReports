<?php

namespace App\Filament\Resources\ReportResource\Pages;

use App\Enums\ManagerStatusEnum;
use App\Filament\Resources\ReportResource;
use App\Models\Employee;
use App\Models\Manager;
use App\Models\Report;
use App\Models\Staff;
use Filament\Actions;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Carbon;

class ListReports extends ListRecords
{
    protected static string $resource = ReportResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    public function getTabs(): array
    {
        $user = auth()->user();
        $today = Carbon::today()->toDateString();

        // 🧩 1. Erişim kısıtlaması
        if ($user->hasRole('super_admin') || $user->can('view_all_reports')) {
            $employeeQuery = Employee::query();
            $reportQuery = Report::query();
        } else {
            $manager = Manager::where('employee_id', $user->employee_id)->first();

            if (! $manager) {
                $reportQuery = Report::whereRaw('1 = 0');
            } else {
                $employeeIds = Staff::where('manager_id', $manager->id)->pluck('employee_id');
                $tcNos = Employee::whereIn('id', $employeeIds)
                    ->where('status', ManagerStatusEnum::ACTIVE)
                    ->pluck('tc_no');

                $reportQuery = Report::whereIn('tc_no', $tcNos);
            }
        }

        // 🧮 2. Sayımlar
        $todayReports = (clone $reportQuery)->whereDate('date', $today);

        $allCount        = (clone $reportQuery)->count();

        $checkedCount    = (clone $todayReports)->whereNotNull('first_reading')
            ->where('status', '==', ManagerStatusEnum::ACTIVE)
            ->count();

        $notCheckedCount = (clone $todayReports)
            ->whereNull('first_reading')
            ->whereNull('last_reading')
            ->where('status', '==', ManagerStatusEnum::ACTIVE)
            ->count();

        $activeCount     = (clone $todayReports)->where('status', ManagerStatusEnum::ACTIVE)->count();
        $inactiveCount   = (clone $todayReports)->where('status', ManagerStatusEnum::INACTIVE)->count();

        // 🧱 3. Sekmeler
        return [
            'all' => Tab::make(__('ui.all'))
                ->badge($allCount)
                ->badgeIcon('heroicon-o-rectangle-stack')
                ->modifyQueryUsing(fn ($query) => $query),

//            'active' => Tab::make(__('ui.active'))
//                ->badge($activeCount)
//                ->badgeColor('success')
//                ->badgeIcon('heroicon-o-check-circle')
//                ->modifyQueryUsing(fn ($query) =>
//                $query->where('status', ManagerStatusEnum::ACTIVE)
//                ),
//
//            'inactive' => Tab::make(__('ui.inactive'))
//                ->badge($inactiveCount)
//                ->badgeColor('danger')
//                ->badgeIcon('heroicon-o-x-circle')
//                ->modifyQueryUsing(fn ($query) =>
//                $query->where('status', ManagerStatusEnum::INACTIVE)
//                ),

            'checked' => Tab::make(__('ui.checked'))
                ->badge($checkedCount)
                ->badgeColor('success')
                ->badgeIcon('heroicon-o-finger-print')
                ->modifyQueryUsing(fn ($query) =>
                $query->whereDate('date', $today)
                    ->whereNotNull('first_reading')
                ),

            'not_checked' => Tab::make(__('ui.not_checked'))
                ->badge($notCheckedCount)
                ->badgeColor('warning')
                ->badgeIcon('heroicon-o-no-symbol')
                ->modifyQueryUsing(fn ($query) =>
                $query->whereDate('date', $today)
                    ->whereNull('first_reading')
                    ->whereNull('last_reading')
                    ->where('status', '!=', ManagerStatusEnum::INACTIVE)
                ),
        ];
    }




//    public function getTabs(): array
//    {
//        $user = auth()->user();
//
//        // Kullanıcının erişebileceği raporları belirle
//        if ($user->hasRole('super_admin') || $user->can('view_all_reports')) {
//            $allQuery = Report::query();
//        } else {
//            // Sadece kendi yönettiği staff'ların report_id'leri
//            $staffReportIds = Staff::whereIn('manager_id', function ($query) use ($user) {
//                $query->select('id')
//                    ->from('managers')
//                    ->where('user_id', $user->id);
//            })->pluck('report_id')->toArray();
//
//            $allQuery = Report::whereIn('id', $staffReportIds);
//        }
//
//        // Sayılar
//        $allCount        = (clone $allQuery)->count();
//        $activeQuery     = (clone $allQuery)->where('status', ManagerStatusEnum::ACTIVE);
//        $inactiveQuery   = (clone $allQuery)->where('status', ManagerStatusEnum::INACTIVE);
//
//        $activeCount     = (clone $activeQuery)->count();
//        $inactiveCount   = (clone $inactiveQuery)->count();
//
//        // ✅ Okutanlar ve okutmayanlar sadece aktif olanlardan
//        $checkedCount    = (clone $activeQuery)->whereNotNull('first_reading')->count();
//        $notCheckedCount = (clone $activeQuery)->whereNull('first_reading')->count();
//
//        return [
//            'all' => Tab::make(__('ui.all'))
//                ->badge($allCount)
//                ->modifyQueryUsing(fn ($query) => $query),
//
//            // ✅ Yalnızca aktif olanlardan okutanlar
//            'checked' => Tab::make(__('ui.checked'))
//                ->label(__('ui.checked'))
//                ->badge($checkedCount)
//                ->badgeIcon('heroicon-o-finger-print')
//                ->badgeColor('success')
//                ->modifyQueryUsing(fn ($query) =>
//                $query->where('status', ManagerStatusEnum::ACTIVE)
//                    ->whereNotNull('first_reading')
//                ),
//
//            // ✅ Yalnızca aktif olanlardan okutmayanlar
//            'not_checked' => Tab::make(__('ui.not_checked'))
//                ->label(__('ui.not_checked'))
//                ->badge($notCheckedCount)
//                ->badgeIcon('heroicon-o-no-symbol')
//                ->badgeColor('warning')
//                ->modifyQueryUsing(fn ($query) =>
//                $query->where('status', ManagerStatusEnum::ACTIVE)
//                    ->whereNull('first_reading')
//                ),
//
//            'active' => Tab::make(__('ui.active'))
//                ->badge($activeCount)
//                ->badgeIcon('heroicon-o-check-circle')
//                ->badgeColor('success')
//                ->modifyQueryUsing(fn ($query) =>
//                $query->where('status', ManagerStatusEnum::ACTIVE)
//                ),
//
//            'inactive' => Tab::make(__('ui.inactive'))
//                ->badge($inactiveCount)
//                ->badgeIcon('heroicon-o-x-circle')
//                ->badgeColor('danger')
//                ->modifyQueryUsing(fn ($query) =>
//                $query->where('status', ManagerStatusEnum::INACTIVE)
//                ),
//        ];
//    }
}
