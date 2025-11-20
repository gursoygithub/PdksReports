<?php

namespace App\Filament\Resources\StaffResource\Pages;

use App\Filament\Resources\StaffResource;
use Filament\Actions;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;

class ViewStaff extends ViewRecord
{
    protected static string $resource = StaffResource::class;

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Card::make()
                    ->schema([
                        Infolists\Components\Fieldset::make(__('ui.manager_information'))
                            ->schema([
                                Infolists\Components\TextEntry::make('employee.tc_no')
                                    ->visible(fn () => auth()->user()->hasRole('super_admin') || auth()->user()->can('view_tc_no'))
                                    ->label(__('ui.tc_no'))
                                    ->icon('heroicon-o-identification')
                                    ->copyable()
                                    ->copyMessage(__('ui.copied_to_clipboard'))
                                    ->placeholder('-'),
                                Infolists\Components\TextEntry::make('employee.full_name')
                                    ->label(__('ui.full_name'))
                                    ->badge()
                                    ->color('primary')
                                    ->icon('heroicon-o-user')
                                    ->placeholder('-'),
                                Infolists\Components\TextEntry::make('employee.latestReport.department_name')
                                    ->label(__('ui.department'))
                                    ->icon('heroicon-o-building-office')
                                    ->placeholder('-'),
                                Infolists\Components\TextEntry::make('employee.latestReport.position_name')
                                    ->label(__('ui.position'))
                                    ->icon('heroicon-o-briefcase')
                                    ->placeholder('-'),
                            ])->columns(3),
                        Infolists\Components\Fieldset::make(__('ui.record_info'))
                            ->schema([
                                Infolists\Components\TextEntry::make('createdBy.name')
                                    ->label(__('ui.created_by'))
                                    ->icon('heroicon-o-user-circle')
                                    ->placeholder('-'),
                                Infolists\Components\TextEntry::make('created_at')
                                    ->label(__('ui.created_at'))
                                    ->dateTime(),
                                Infolists\Components\TextEntry::make('updatedBy.name')
                                    ->visible(fn ($record) => filled($record->updated_by)) // Sadece güncelleyen varsa göster
                                    ->label(__('ui.last_updated_by'))
                                    ->icon('heroicon-o-user-circle')
                                    ->placeholder('-'),
                                Infolists\Components\TextEntry::make('updated_at')
                                    ->visible(fn ($record) => filled($record->updated_by))
                                    ->label(__('ui.last_updated_at'))
                                    ->dateTime(),
                            ])->columns(4),
                    ]),
            ]);
    }
}
