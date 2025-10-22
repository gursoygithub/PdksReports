<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
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

        try {
            $staff = Report::query()->findOrFail($data['name']);

            $data['tc_no'] = $staff->tc_no;
            $data['name'] = $staff->full_name;
            $data['status'] = \App\Enums\ManagerStatusEnum::ACTIVE;
        } catch (\Exception $e) {
            session()->flash('error', 'Kullanıcı bulunamadı.');
        }


//        if (isset($data['project_id']) && $data['project_id'] !== $this->record->project_id) {
//            $project = \Illuminate\Support\Facades\DB::connection('sqlsrv')
//                ->table('dbo.TumProjeler')
//                ->where('KOD', $data['project_id'])
//                ->first();
//
//            if ($project) {
//                $data['project_id'] = $project->KOD;
//                $data['project_name'] = $project->AD;
//            } else {
//                // uyarı ver
//                session()->flash('error', 'Proje bulunamadı.');
//            }
//        }

        return $data;
    }
}
