<?php

namespace App\Filament\Resources;

use App\Enums\ActiveStatusEnum;
use App\Enums\ManagerStatusEnum;
use App\Filament\Resources\ManagerResource\Pages;
use App\Filament\Resources\ManagerResource\RelationManagers;
use App\Models\Employee;
use App\Models\Manager;
use App\Models\Report;
use Filament\Forms;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ManagerResource extends Resource
{
    protected static ?string $model = Manager::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    public static function getModelLabel(): string
    {
        return __('ui.manager');
    }

    public static function getPluralModelLabel(): string
    {
        return __('ui.managers');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('ui.panel_management');
    }

    public static function getNavigationBadge(): ?string
    {
        if (auth()->user()->hasRole('super_admin') || auth()->user()->can('view_all_managers')) {
            return static::getModel()::count();
        } else {
            return static::getModel()::where('created_by', auth()->id())->count();
        }
    }

    protected static ?int $navigationSort = 200;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                \Filament\Forms\Components\Card::make()
                    ->schema([
                        Fieldset::make(__('ui.manager_information'))
                            ->columns(1)
                            ->schema([
                                Forms\Components\SpatieMediaLibraryFileUpload::make('images')
                                    ->hidden()
                                ->label(__('ui.images'))
                                ->helperText(__('ui.task_photo_helper_text'))
                                ->collection('manager_profile')
                                ->downloadable()
                                ->openable()
                                ->maxFiles(10)
                                ->image()
                                ->required()
                                ->validationMessages([
                                    'required' => __('ui.required'),
                                ])
                                ->columnSpanFull(),
                                Forms\Components\Select::make('employee_id')
                                    ->label(__('ui.manager'))
                                    ->searchable()
                                    ->preload()
                                    ->options(function (callable $get, ?Manager $record = null) {
                                        $existingUserIds = Manager::query()
                                            ->when($record, function ($query) use ($record) {
                                                return $query->where('id', '!=', $record->id);
                                            })
                                            ->pluck('employee_id')
                                            ->toArray();

                                        $query = \App\Models\User::query()
                                            ->whereHas('employee', function ($q) {
                                                $q->where('is_manager', false)
                                                    ->where('status', ManagerStatusEnum::ACTIVE);
                                            })
                                            ->where('id', '!=', Auth::id());

                                        if (!auth()->user()?->hasRole('super_admin') && !auth()->user()?->can('view_all_managers')) {
                                            $query->where('created_by', auth()->id());
                                        }

                                        $users = $query->whereNotIn('employee_id', $existingUserIds)
                                            ->orderBy('name')
                                            ->get();

                                        return $users->pluck('name', 'id')->toArray();
                                    })
                                    ->required()
                                    ->validationMessages([
                                        'required' => __('ui.required'),
                                        ]),
                            ]),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('updated_at', 'desc')
            ->paginated([5, 10, 25, 50])
            ->columns([
                Tables\Columns\TextColumn::make('user.employee.tc_no')
                    ->visible(fn ($record) => auth()->user()->hasRole('super_admin') || auth()->user()->can('view_tc_no'))
                    ->label(__('ui.tc_no'))
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
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
                Tables\Columns\TextColumn::make('user.employee.latestReport.department_name')
                    ->label(__('ui.department'))
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('user.employee.latestReport.position_name')
                    ->label(__('ui.position'))
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('staffs_count')
                    ->label(__('ui.staffs_count'))
                    ->counts('staffs')
                    ->badge()
                    ->color('info')
                    //->alignCenter()
                    ->sortable(),
                Tables\Columns\TextColumn::make('user.status')
                    ->label(__('ui.status'))
                    ->badge()
                    ->sortable(),
                Tables\Columns\TextColumn::make('createdBy.name')
                    ->visible(fn () => auth()->user()->hasRole('super_admin'))
                    ->label(__('ui.created_by'))
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('ui.created_at'))
                    ->dateTime(),
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
            ->actions([
                Tables\Actions\ActionGroup::make(
                    [
                        Tables\Actions\ViewAction::make(),
                        Tables\Actions\EditAction::make(),
                        Tables\Actions\DeleteAction::make()
                            ->visible(fn ($record) => $record->staffs()->count() === 0)
                            ->requiresConfirmation()
                            ->action(function ($record) {
                                DB::transaction(function () use ($record) {
                                    try {
                                        //set is_manager to false for the related user
                                        $user = $record->user->employee;

                                        if ($user) {
                                            $user->is_manager = false;
                                            $user->save();
                                        }

                                        // Set is_staff to false for all related employees
                                        foreach ($record->staffs as $staff) {
                                            $employee = $staff->employee;
                                            if ($employee) {
                                                $employee->is_staff = false;
                                                $employee->save();
                                            }
                                        }

                                        // Soft delete related staffs
                                        foreach ($record->staffs as $staff) {
                                            $staff->deleted_by = Auth::id();
                                            $staff->deleted_at = now();
                                            $staff->save();
                                        }

                                        // Soft delete the manager record
                                        $record->deleted_by = Auth::id();
                                        $record->deleted_at = now();
                                        $record->save();

                                        Notification::make()
                                            ->title(__('ui.deletion_successful'))
                                            ->success()
                                            ->send();
                                    } catch (\Exception $e) {
                                        Notification::make()
                                            ->title(__('ui.deletion_failed'))
                                            ->danger()
                                            ->send();
                                    }
                                });
                            })
                    ]
                ),
            ])
            ->bulkActions([
                //
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\StaffsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListManagers::route('/'),
            'create' => Pages\CreateManager::route('/create'),
            'edit' => Pages\EditManager::route('/{record}/edit'),
            'view' => Pages\ViewManager::route('/{record}')
        ];
    }
}
