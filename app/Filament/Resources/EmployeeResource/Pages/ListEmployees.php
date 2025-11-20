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

    public function getTabs(): array
    {
        $tabs = [];

        $hasPermission = auth()->user()->hasRole('super_admin') || auth()->user()->can('view_all_employees');

        $query = Employee::query();

        if (! $hasPermission) {
            $manager = \App\Models\Manager::where('employee_id', auth()->user()->employee_id)->first();
            if ($manager) {
                $employeeIds = \App\Models\Staff::where('manager_id', $manager->id)->pluck('employee_id');
                $query->whereIn('id', $employeeIds)
                      ->where('status', ManagerStatusEnum::ACTIVE);
            } else {
                $query->whereRaw('1 = 0'); // No access
            }
        }

        $totalCount = $query->count();

        $tabs['all'] = Tab::make(__('ui.all'))
            ->badge($query->count())
            ->modifyQueryUsing(function ($query) use ($hasPermission) {
                if (! $hasPermission) {
                    $manager = \App\Models\Manager::where('employee_id', auth()->user()->employee_id)->first();
                    if ($manager) {
                        $employeeIds = \App\Models\Staff::where('manager_id', $manager->id)->pluck('employee_id');
                        $query->whereIn('id', $employeeIds)
                              ->where('status', ManagerStatusEnum::ACTIVE);
                    } else {
                        $query->whereRaw('1 = 0'); // No access
                    }
                }
                return $query;
            });

        $tabs['active'] = Tab::make(__('ui.active'))
            ->badge(
                Employee::query()
                    ->when(! $hasPermission, function ($query) {
                        $manager = \App\Models\Manager::where('employee_id', auth()->user()->employee_id)->first();
                        if ($manager) {
                            $employeeIds = \App\Models\Staff::where('manager_id', $manager->id)->pluck('employee_id');
                            $query->whereIn('id', $employeeIds)
                                  ->where('status', ManagerStatusEnum::ACTIVE);
                        } else {
                            $query->whereRaw('1 = 0'); // No access
                        }
                    })
                    ->where('status', ManagerStatusEnum::ACTIVE)
                    ->count()
            )
            ->badgeIcon('heroicon-o-check-circle')
            ->badgeColor('success')
            ->modifyQueryUsing(function ($query) use ($hasPermission) {
                $query->where('status', ManagerStatusEnum::ACTIVE);
                if (! $hasPermission) {
                    $manager = \App\Models\Manager::where('employee_id', auth()->user()->employee_id)->first();
                    if ($manager) {
                        $employeeIds = \App\Models\Staff::where('manager_id', $manager->id)->pluck('employee_id');
                        $query->whereIn('id', $employeeIds)
                              ->where('status', ManagerStatusEnum::ACTIVE);
                    } else {
                        $query->whereRaw('1 = 0'); // No access
                    }
                }
                return $query;
            });

        $tabs['inactive'] = Tab::make(__('ui.inactive'))
            ->badge(
                Employee::query()
                    ->when(! $hasPermission, function ($query) {
                        $manager = \App\Models\Manager::where('employee_id', auth()->user()->employee_id)->first();
                        if ($manager) {
                            $employeeIds = \App\Models\Staff::where('manager_id', $manager->id)->pluck('employee_id');
                            $query->whereIn('id', $employeeIds)
                                  ->where('status', ManagerStatusEnum::ACTIVE);
                        } else {
                            $query->whereRaw('1 = 0'); // No access
                        }
                    })
                    ->where('status', ManagerStatusEnum::INACTIVE)
                    ->count()
            )
            ->badgeIcon('heroicon-o-x-circle')
            ->badgeColor('danger')
            ->modifyQueryUsing(function ($query) use ($hasPermission) {
                $query->where('status', ManagerStatusEnum::INACTIVE);
                if (! $hasPermission) {
                    $manager = \App\Models\Manager::where('employee_id', auth()->user()->employee_id)->first();
                    if ($manager) {
                        $employeeIds = \App\Models\Staff::where('manager_id', $manager->id)->pluck('employee_id');
                        $query->whereIn('id', $employeeIds)
                              ->where('status', ManagerStatusEnum::ACTIVE);
                    } else {
                        $query->whereRaw('1 = 0'); // No access
                    }
                }
                return $query;
            });


        return $tabs;

    }
}
