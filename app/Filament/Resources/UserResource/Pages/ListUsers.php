<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use App\Models\User;
use Filament\Actions;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;

class ListUsers extends ListRecords
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    // Define tabs for filtering users based on their status
//    public function getTabs(): array
//    {
//        $tabs = [];
//
//        $tabs['all'] = Tab::make(__('ui.all'))
//            ->badge(User::count());
//
//        $tabs['active'] = Tab::make(__('ui.active'))
//            ->badge(User::where('status', 1)->count())
//            ->badgeIcon('heroicon-o-check-circle')
//            ->badgeColor('success')
//            ->modifyQueryUsing(function ($query) {
//                return $query->where('status', 1);
//            });
//
//        $tabs['inactive'] = Tab::make(__('ui.inactive'))
//            ->badge(User::where('status', 0)->count())
//            ->badgeIcon('heroicon-o-x-circle')
//            ->badgeColor('danger')
//            ->modifyQueryUsing(function ($query) {
//                return $query->where('status', 0);
//            });
//
//        return $tabs;
//    }

    public function getTabs(): array
{
    $tabs = [];
    $canViewAllUsers = auth()->user()->hasRole('super_admin') || auth()->user()->can('view_all_users');

    // "All" tab
    $query = User::query();
    if (!$canViewAllUsers) {
        $query->where('created_by', auth()->id());
    }

    $tabs['all'] = Tab::make(__('ui.all'))
        ->badge($query->count())
        ->modifyQueryUsing(function ($query) use ($canViewAllUsers) {
            if (!$canViewAllUsers) {
                return $query->where('created_by', auth()->id());
            }
            return $query;
        });

    // "Active" tab
    $activeQuery = User::query()->where('status', 1);
    if (!$canViewAllUsers) {
        $activeQuery->where('created_by', auth()->id());
    }

    $tabs['active'] = Tab::make(__('ui.active'))
        ->badge($activeQuery->count())
        ->badgeIcon('heroicon-o-check-circle')
        ->badgeColor('success')
        ->modifyQueryUsing(function ($query) use ($canViewAllUsers) {
            $query->where('status', 1);
            if (!$canViewAllUsers) {
                $query->where('created_by', auth()->id());
            }
            return $query;
        });

    // "Inactive" tab
    $inactiveQuery = User::query()->where('status', 0);
    if (!$canViewAllUsers) {
        $inactiveQuery->where('created_by', auth()->id());
    }

    $tabs['inactive'] = Tab::make(__('ui.inactive'))
        ->badge($inactiveQuery->count())
        ->badgeIcon('heroicon-o-x-circle')
        ->badgeColor('danger')
        ->modifyQueryUsing(function ($query) use ($canViewAllUsers) {
            $query->where('status', 0);
            if (!$canViewAllUsers) {
                $query->where('created_by', auth()->id());
            }
            return $query;
        });

    return $tabs;
}
}
