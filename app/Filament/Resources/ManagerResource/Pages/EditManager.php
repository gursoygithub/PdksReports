<?php

namespace App\Filament\Resources\ManagerResource\Pages;

use App\Filament\Resources\ManagerResource;
use App\Models\Report;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class EditManager extends EditRecord
{
    protected static string $resource = ManagerResource::class;

    protected function getHeaderActions(): array
    {
        return [
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

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', [
            'record' => $this->record,
        ]);
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['updated_by'] = auth()->id();

        return $data;
    }
}
