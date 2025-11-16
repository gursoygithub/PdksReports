<?php

namespace App\Filament\Resources\EmployeeResource\Pages;

use App\Enums\ManagerStatusEnum;
use App\Filament\Resources\EmployeeResource;
use App\Models\Employee;
use Filament\Actions;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;

class ListEmployees extends ListRecords
{
    protected static string $resource = EmployeeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

//    public function getTabs(): array
//    {
//        $tabs = [];
//        $canViewAllManagers = auth()->user()->hasRole('super_admin') || auth()->user()->can('view_all_employees');
//
//        // "All" tab
//        $allQuery = Employee::query();
//        if (!$canViewAllManagers) {
//            $allQuery->where('created_by', auth()->id());
//        }
//
//        $tabs['all'] = Tab::make(__('ui.all'))
//            ->badge($allQuery->count())
//            ->modifyQueryUsing(function ($query) use ($canViewAllManagers) {
//                if (!$canViewAllManagers) {
//                    $query->where('created_by', auth()->id());
//                }
//                return $query;
//            });
//
//        // "Active" tab
//        $activeQuery = Employee::query()->where('status', ManagerStatusEnum::ACTIVE);
//        if (!$canViewAllManagers) {
//            $activeQuery->where('created_by', auth()->id());
//        }
//
//        // Active tab
//        $tabs['active'] = Tab::make(__('ui.active'))
//            ->badge($activeQuery->count())
//            ->badgeIcon('heroicon-o-check-circle')
//            ->badgeColor('success')
//            ->modifyQueryUsing(function ($query) use ($canViewAllManagers) {
//                $query->where('status', ManagerStatusEnum::ACTIVE);
//                if (!$canViewAllManagers) {
//                    $query->where('created_by', auth()->id());
//                }
//                return $query;
//            });
//
//        // "Inactive" tab
//        $inactiveQuery = Employee::query()->where('status', ManagerStatusEnum::INACTIVE);
//        if (!$canViewAllManagers) {
//            $inactiveQuery->where('created_by', auth()->id());
//        }
//
//        // Inactive tab
//        $tabs['inactive'] = Tab::make(__('ui.inactive'))
//            ->badge($inactiveQuery->count())
//            ->badgeIcon('heroicon-o-x-circle')
//            ->badgeColor('danger')
//            ->modifyQueryUsing(function ($query) use ($canViewAllManagers) {
//                $query->where('status', ManagerStatusEnum::INACTIVE);
//                if (!$canViewAllManagers) {
//                    $query->where('created_by', auth()->id());
//                }
//                return $query;
//            });
//
//        return $tabs;
//
//    }
}
