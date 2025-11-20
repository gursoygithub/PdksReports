<?php

namespace App\Filament\Resources\StaffResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ReportsRelationManager extends RelationManager
{
    protected static string $relationship = 'reports';

    /**
     * @return string|null
     */
    public static function getModelLabel(): ?string
    {
        return __('ui.card_reading_report');
    }

    protected static function getPluralModelLabel(): ?string
    {
        return __('ui.card_reading_reports');
    }

    protected function getTableHeading(): string|Htmlable|null
    {
        return __('ui.card_reading_reports');
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                //
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('date', 'desc')
            ->paginated([5, 10, 25, 50])
            ->columns([
                Tables\Columns\TextColumn::make('date')
                    ->label(__('ui.date'))
                    ->badge()
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('first_reading')
                    ->label(__('ui.first_reading'))
                    ->badge()
                    ->color('success')
                    ->Time(),
                Tables\Columns\TextColumn::make('last_reading')
                    ->label(__('ui.last_reading'))
                    ->badge()
                    ->color('success')
                    ->Time(),
                Tables\Columns\TextColumn::make('working_time')
                    ->label(__('ui.working_time'))
                    ->badge()
                    ->color('success'),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                //
            ])
            ->bulkActions([
                //
            ]);
    }

    protected function canCreate(): bool
    {
        return false;
    }

    protected function canEdit(Model $record): bool
    {
        return false;
    }

    protected function canDelete(Model $record): bool
    {
        return false;
    }
}
