<?php

namespace App\Filament\Resources\ReportResource\Pages;

use App\Enums\ManagerStatusEnum;
use App\Filament\Resources\ReportResource;
use App\Models\Report;
use App\Models\Staff;
use Filament\Actions;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;

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

        // Kullanıcının erişebileceği raporları belirle
        if ($user->hasRole('super_admin') || $user->can('view_all_reports')) {
            $allQuery = Report::query();
        } else {
            // Sadece kendi yönettiği staff'ların report_id'leri
            $staffReportIds = Staff::whereIn('manager_id', function ($query) use ($user) {
                $query->select('id')
                    ->from('managers')
                    ->where('user_id', $user->id);
            })->pluck('report_id')->toArray();

            $allQuery = Report::whereIn('id', $staffReportIds);
        }

        // Sayılar
        $allCount      = (clone $allQuery)->count();
        $activeQuery   = (clone $allQuery)->where('status', ManagerStatusEnum::ACTIVE);
        $inactiveQuery = (clone $allQuery)->where('status', ManagerStatusEnum::INACTIVE);

        $activeCount   = (clone $activeQuery)->count();
        $inactiveCount = (clone $inactiveQuery)->count();

        // ✅ Okutanlar ve okutmayanlar sadece aktif olanlardan ve bugüne ait (hem badge hem sorgu)
        $today = today();

        $checkedQuery = (clone $activeQuery)
            ->whereDate('date', $today)
            ->whereNotNull('first_reading');

        $notCheckedQuery = (clone $activeQuery)
            ->whereDate('date', $today)
            ->whereNull('first_reading');

        $checkedCount    = (clone $checkedQuery)->count();
        $notCheckedCount = (clone $notCheckedQuery)->count();

        return [
            'all' => Tab::make(__('ui.all'))
                ->badge($allCount)
                ->modifyQueryUsing(fn ($query) => $query),

            // ✅ Günlük okutanlar (sadece aktiflerden)
            'checked' => Tab::make(__('ui.checked'))
                ->label(__('ui.checked'))
                ->badge($checkedCount)
                ->badgeIcon('heroicon-o-finger-print')
                ->badgeColor('success')
                ->modifyQueryUsing(fn ($query) =>
                $query->where('status', ManagerStatusEnum::ACTIVE)
                    ->whereDate('date', today())
                    ->whereNotNull('first_reading')
                ),

            // ✅ Günlük okutmayanlar (sadece aktiflerden)
            'not_checked' => Tab::make(__('ui.not_checked'))
                ->label(__('ui.not_checked'))
                ->badge($notCheckedCount)
                ->badgeIcon('heroicon-o-no-symbol')
                ->badgeColor('warning')
                ->modifyQueryUsing(fn ($query) =>
                $query->where('status', ManagerStatusEnum::ACTIVE)
                    ->whereDate('date', today())
                    ->whereNull('first_reading')
                ),

            'active' => Tab::make(__('ui.active'))
                ->badge($activeCount)
                ->badgeIcon('heroicon-o-check-circle')
                ->badgeColor('success')
                ->modifyQueryUsing(fn ($query) =>
                $query->where('status', ManagerStatusEnum::ACTIVE)
                ),

            'inactive' => Tab::make(__('ui.inactive'))
                ->badge($inactiveCount)
                ->badgeIcon('heroicon-o-x-circle')
                ->badgeColor('danger')
                ->modifyQueryUsing(fn ($query) =>
                $query->where('status', ManagerStatusEnum::INACTIVE)
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
