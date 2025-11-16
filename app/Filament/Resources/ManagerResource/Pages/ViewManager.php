<?php

namespace App\Filament\Resources\ManagerResource\Pages;

use App\Filament\Resources\ManagerResource;
use App\Models\Report;
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
                ->visible(fn ($record) => $record->staffs()->count() === 0)
                ->action(function ($record) {
                    DB::transaction(function () use ($record) {
                        try {
                            //set is_manager to false for the related user employee
                            $user = $record->user->employee;
                            if ($user) {
                                $user->is_manager = false;
                                $user->save();
                            }

                            // Set is_staff to false for all related staffs' employees
                            foreach ($record->staffs as $staff) {
                                $employee = $staff->employee;
                                if ($employee) {
                                    $employee->is_staff = false;
                                    $employee->save();
                                }
                            }

                            // Soft delete all related staffs
                            foreach ($record->staffs as $staff) {
                                $staff->deleted_by = Auth::id();
                                $staff->deleted_at = now();
                                $staff->save();
                                $staff->delete();
                            }

                            // Soft delete the manager record
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
                                Infolists\Components\TextEntry::make('employee.tc_no')
                                    ->visible(fn () => auth()->user()->hasRole('super_admin') || auth()->user()->can('view_tc_no'))
                                    ->label(__('ui.tc_no'))
                                    ->icon('heroicon-o-identification')
                                    ->copyable()
                                    ->copyMessage(__('ui.copied_to_clipboard'))
                                    ->placeholder('-'),
                                Infolists\Components\TextEntry::make('user.name')
                                    ->label(__('ui.full_name'))
                                    ->badge()
                                    ->color('primary')
                                    ->icon('heroicon-o-user')
                                    ->placeholder('-'),
                                Infolists\Components\TextEntry::make('user.email')
                                    ->label(__('ui.email'))
                                    ->icon('heroicon-o-envelope')
                                    ->copyable()
                                    ->copyMessage(__('ui.copied_to_clipboard'))
                                    ->placeholder('-'),
                                Infolists\Components\TextEntry::make('user.employee.latestReport.department_name')
                                    ->label(__('ui.department'))
                                    ->icon('heroicon-o-building-office')
                                    ->placeholder('-'),
                                Infolists\Components\TextEntry::make('user.employee.latestReport.position_name')
                                    ->label(__('ui.position'))
                                    ->icon('heroicon-o-briefcase')
                                    ->placeholder('-'),
                                Infolists\Components\TextEntry::make('user.status')
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
