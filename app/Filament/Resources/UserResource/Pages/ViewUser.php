<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewUser extends ViewRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
            Actions\DeleteAction::make(),
//                ->hidden(fn ($record) =>
//                    $record->id === auth()->user()->id ||
//                    $record->cards()->count() > 0 ||
//                    $record->visitors()->count() > 0 ||
//                    $record->visitorCards()->count() > 0),
        ];
    }
}
