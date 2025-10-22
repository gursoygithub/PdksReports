<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use App\Models\Report;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\DB;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by'] = auth()->user()->id;

        try {
            $staff = Report::query()->findOrFail($data['name']);
            $data['tc_no'] = $staff->tc_no;
            $data['name'] = $staff->full_name;
            $data['status'] = \App\Enums\ManagerStatusEnum::ACTIVE;
        } catch (\Exception $e) {
            session()->flash('error', 'Kullanıcı bulunamadı.');
        }

//        if (isset($data['project_id'])) {
//
//            $project = DB::connection('sqlsrv')
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
//        } else {
//            // uyarı ver
//            session()->flash('error', 'Proje kodu boş olamaz.');
//        }

        return $data;
    }
}
