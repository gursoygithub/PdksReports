<?php

namespace App\Filament\Resources;

use App\Enums\BooleanStatusEnum;
use App\Enums\ManagerStatusEnum;
use App\Filament\Resources\EmployeeResource\Pages;
use App\Filament\Resources\EmployeeResource\RelationManagers;
use App\Models\Employee;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class EmployeeResource extends Resource
{
    protected static ?string $model = Employee::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    public static function getModelLabel(): string
    {
        return __('ui.employee');
    }

    public static function getPluralModelLabel(): string
    {
        return __('ui.employees');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('ui.panel_management');
    }

    public static function getNavigationBadge(): ?string
    {
        $hasPermission = auth()->user()->hasRole('super_admin') || auth()->user()->can('view_all_employees');

        if ($hasPermission) {
            $count = Employee::count();
            return $count > 0 ? (string)$count : null;
        } else {
            $manager = \App\Models\Manager::where('employee_id', auth()->user()->employee_id)->first();
            if (! $manager) {
                return null;
            } else {
                $employeeIds = \App\Models\Staff::where('manager_id', $manager->id)->pluck('employee_id');
                $count = Employee::whereIn('id', $employeeIds)
                    ->where('status', ManagerStatusEnum::ACTIVE)
                    ->count();
                return $count > 0 ? (string)$count : null;
            }
        }
    }


    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                //
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('first_name')
            ->paginated([5, 10, 25, 50])
            ->columns([
                Tables\Columns\TextColumn::make('tc_no')
                    ->visible(fn ($record) => auth()->user()->hasRole('super_admin') || auth()->user()->can('view_tc_no'))
                    ->label(__('ui.tc_no'))
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('full_name')
                        ->label(__('ui.full_name'))
                        ->sortable(query: fn(Builder $query, string $direction): Builder =>
                            $query->orderBy('first_name', $direction)->orderBy('last_name', $direction)
                        )
                        ->searchable(['first_name', 'last_name']),
                Tables\Columns\TextColumn::make('status')
                    ->label(__('ui.status'))
                    ->badge(),
                Tables\Columns\TextColumn::make('create_time')
                    ->label(__('ui.create_time'))
                    ->dateTime(),
                Tables\Columns\TextColumn::make('update_time')
                    ->label(__('ui.update_time'))
                    ->dateTime(),
                Tables\Columns\TextColumn::make('is_manager')
                    ->label(__('ui.is_manager'))
                    ->badge()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('is_staff')
                    ->label(__('ui.has_manager'))
                    ->badge()
                    ->toggleable(isToggledHiddenByDefault: true),
//                Tables\Columns\TextColumn::make('is_mailable')
//                    ->label(__('ui.is_mailable'))
//                    ->badge()
//                    ->toggleable(isToggledHiddenByDefault: true),

            ])
            ->filters([
                // amir mi değil mi filtresi
                Tables\Filters\SelectFilter::make('is_manager')
                    ->label(__('ui.is_manager'))
                    ->options(BooleanStatusEnum::class),
                // personel mi değil mi filtresi
                Tables\Filters\SelectFilter::make('is_staff')
                    ->label(__('ui.has_manager'))
                    ->options(BooleanStatusEnum::class),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                //
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListEmployees::route('/'),
            'create' => Pages\CreateEmployee::route('/create'),
            'edit' => Pages\EditEmployee::route('/{record}/edit'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }
}
