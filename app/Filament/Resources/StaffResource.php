<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ManagerResource\RelationManagers\StaffsRelationManager;
use App\Filament\Resources\StaffResource\Pages;
use App\Filament\Resources\StaffResource\RelationManagers;
use App\Models\Staff;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class StaffResource extends Resource
{
    protected static ?string $model = Staff::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    //protected static bool $shouldRegisterNavigation = false;

    public static function getModelLabel(): string
    {
        return __('ui.staff');
    }

    public static function getPluralModelLabel(): string
    {
        return __('ui.staffs');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('ui.report_management');
    }

//    public static function getNavigationBadge(): ?string
//    {
//        if (auth()->user()?->hasRole('super_admin') || auth()->user()?->can('view_all_staff')) {
//            return static::getModel()::count();
//        }
//        return static::getModel()::whereIn('manager_id', function ($query) {
//            $query->select('id')
//                ->from('managers')
//                ->where('employee_id', auth()->id());
//        })->count();
//    }

//    public static function getEloquentQuery(): Builder
//    {
//        return parent::getEloquentQuery()->where(function ($query) {
//            if (auth()->user()?->hasRole('super_admin') || auth()->user()?->can('view_all_staff')
//            ) {
//                return $query;
//            }
//            return $query->whereIn('manager_id', function ($subQuery) {
//                $subQuery->select('id')
//                    ->from('managers')
//                    ->where('user_id', auth()->id());
//            });
//        });
//    }

    protected static ?int $navigationSort = 3;

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
            ->defaultSort('updated_at', 'desc')
            ->paginated([5, 10, 25, 50])
            ->columns([
                Tables\Columns\TextColumn::make('employee.tc_no')
                    ->visible(fn () => auth()->user()->hasRole('super_admin') || auth()->user()->can('view_tc_no'))
                    ->label(__('ui.tc_no'))
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
//                Tables\Columns\TextColumn::make('employee.full_name')
//                    ->label(__('ui.full_name'))
//                    ->badge()
//                    ->color('primary')
//                    ->searchable()
//                    ->sortable(),
                Tables\Columns\TextColumn::make('employee.full_name')
                    ->label(__('ui.full_name'))
                    ->badge()
                    ->color('primary')
                    ->sortable(query: fn ($query, $direction) =>
                        $query->orderBy(
                            \App\Models\Employee::selectRaw("CONCAT(first_name, ' ', last_name)")
                                ->whereColumn('employees.id', 'staff.employee_id'),
                            $direction
                        )
                    )
                    ->searchable(query: fn ($query, $search) =>
                        $query->whereHas('employee', fn ($q) =>
                        $q->whereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ["%{$search}%"])
                        )
                    ),
        Tables\Columns\TextColumn::make('employee.latestReport.department_name')
                    ->label(__('ui.department'))
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('employee.latestReport.position_name')
                    ->label(__('ui.position'))
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('manager.user.name')
                    ->visible(fn () => auth()->user()->hasRole('super_admin') || auth()->user()->can('view_all_managers'))
                    ->label(__('ui.manager'))
                    ->badge()
                    ->color('primary')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('createdBy.name')
                    ->visible(fn () => auth()->user()->hasRole('super_admin'))
                    ->label(__('ui.created_by'))
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('ui.created_at'))
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('updatedBy.name')
                    ->label(__('ui.updated_by'))
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label(__('ui.updated_at'))
                    ->getStateUsing(fn ($record) => $record->updated_by ? $record->updated_at : null)
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\ExportAction::make()
                    ->exporter(\App\Filament\Exports\StaffExporter::class)
                    ->label(__('ui.export'))
                    ->modalHeading(__('ui.export_staff_reports'))
                    ->icon('heroicon-o-arrow-down-tray')
                    ->visible(fn () => auth()->user()->hasRole('super_admin') || auth()->user()->can('export_staff')),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make(),
//                    Tables\Actions\EditAction::make(),
//                    Tables\Actions\DeleteAction::make(),
                ])
            ])
            ->bulkActions([
                //
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\ReportsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListStaff::route('/'),
            'create' => Pages\CreateStaff::route('/create'),
            'edit' => Pages\EditStaff::route('/{record}/edit'),
            'view' => Pages\ViewStaff::route('/{record}'),
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
}
