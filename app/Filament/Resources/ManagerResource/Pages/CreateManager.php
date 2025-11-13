<?php

namespace App\Filament\Resources\ManagerResource\Pages;

use App\Filament\Resources\ManagerResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateManager extends CreateRecord
{
    protected static string $resource = ManagerResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', [
            'record' => $this->record,
        ]);
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by'] = auth()->id();

        // Set the is_manager field of the related employee to YES
        $user = \App\Models\User::find($data['user_id']);

        if (!$user) {
            throw new \Exception('Kullanıcı bulunamadı.');
        }

        $employee = $user->employee;

        if ($employee) {
            $employee->is_manager = \App\Enums\BooleanStatusEnum::YES;
            $employee->save();
        } else {
            throw new \Exception('Çalışan bulunamadı.');
        }

        return $data;
    }
}
