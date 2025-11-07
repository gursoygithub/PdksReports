<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use App\Models\Employee;
use App\Models\Report;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
//                ->hidden(fn ($record) =>
//                    $record->id === auth()->user()->id ||
//                    $record->cards()->count() > 0 ||
//                    $record->visitors()->count() > 0 ||
//                    $record->visitorCards()->count() > 0),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('view', [
            'record' => $this->record,
        ]);
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['updated_by'] = auth()->user()->id;

        // employee_id değiştiyse güncelle
        if (isset($data['employee_id']) && $data['employee_id'] !== $this->record->employee_id) {
            $data['tc_no'] = $data['employee_id'];

            $employee = Employee::find($data['employee_id']);

            if ($employee) {
                $data['name'] = $employee->full_name;
            } else {
                session()->flash('error', 'Çalışan bulunamadı.');
            }
        }

        return $data;
    }
}
