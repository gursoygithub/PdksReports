<?php

namespace App\Filament\Resources\ManagerResource\Pages;

use App\Filament\Resources\ManagerResource;
use Filament\Actions;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ViewManager extends ViewRecord
{
    protected static string $resource = ManagerResource::class;

//    public function getRelationManagers(): array
//    {
//        return [
//            ManagerResource\RelationManagers\ReportsRelationManager::class,
//        ];
//    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
                ->mutateFormDataUsing(function (array $data): array {
                    $data['updated_by'] = auth()->id();
                    return $data;
                }),
            Actions\DeleteAction::make()
                ->requiresConfirmation()
                ->action(function ($record) {
                    DB::transaction(function () use ($record) {
                        try {
                            $record->deleted_by = Auth::id();
                            $record->deleted_at = now();
                            $record->save();

                            $record->delete();

                            Notification::make()
                                ->title(__('ui.deletion_successful'))
                                ->success()
                                ->send();

                            $this->redirect(ManagerResource::getUrl('index'));
                        } catch (\Exception $e) {
                            Notification::make()
                                ->title(__('ui.deletion_failed'))
                                ->danger()
                                ->send();
                        }
                    });
                })
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Card::make()
                    ->schema([
                        Infolists\Components\Fieldset::make(__('ui.manager_information'))
                            ->schema([
                                Infolists\Components\TextEntry::make('report.tc_no')
                                    ->visible(fn () => auth()->user()->hasRole('super_admin') || auth()->user()->can('view_tc_no'))
                                    ->label(__('ui.tc_no'))
                                    ->icon('heroicon-o-identification')
                                    ->copyable()
                                    ->copyMessage(__('ui.copied_to_clipboard'))
                                    ->placeholder('-'),
                                Infolists\Components\TextEntry::make('report.full_name')
                                    ->label(__('ui.full_name'))
                                    ->badge()
                                    ->color('primary')
                                    ->icon('heroicon-o-user')
                                    ->placeholder('-'),
                                Infolists\Components\TextEntry::make('email')
                                    ->label(__('ui.email'))
                                    ->icon('heroicon-o-envelope')
                                    ->copyable()
                                    ->copyMessage(__('ui.copied_to_clipboard'))
                                    ->placeholder('-'),
                                Infolists\Components\TextEntry::make('report.department_name')
                                    ->label(__('ui.department'))
                                    ->icon('heroicon-o-building-office')
                                    ->placeholder('-'),
                                Infolists\Components\TextEntry::make('report.position_name')
                                    ->label(__('ui.position'))
                                    ->icon('heroicon-o-briefcase')
                                    ->placeholder('-'),
                                Infolists\Components\TextEntry::make('status')
                                    ->label(__('ui.status'))
                                    ->badge()
                                    ->placeholder('-'),
                            ])->columns(4),
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
