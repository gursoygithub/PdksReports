<?php

namespace App\Filament\Widgets;

use App\Enums\ManagerStatusEnum;
use App\Models\Employee;
use App\Models\Manager;
use App\Models\Report;
use App\Models\Staff;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Carbon;

class ReportsOverview extends BaseWidget
{
    protected function getHeading(): ?string
    {
        return __('ui.cart_reading_reports');
    }

    protected function getStats(): array
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

        return [
            Stat::make(__('ui.all'), $allCount)
                ->icon('heroicon-o-identification')
                //->description(__('ui.all'))
                ->descriptionColor('primary'),

            Stat::make(__('ui.daily_report'), $checkedCount)
                ->icon('heroicon-o-identification')
                ->description(__('ui.checked'))
                ->descriptionIcon('heroicon-o-finger-print')
                ->descriptionColor('success'),

            Stat::make(__('ui.daily_report'), $notCheckedCount)
                ->icon('heroicon-o-identification')
                ->description(__('ui.not_checked'))
                ->descriptionIcon('heroicon-o-no-symbol')
                ->descriptionColor('warning'),
        ];

    }
}
