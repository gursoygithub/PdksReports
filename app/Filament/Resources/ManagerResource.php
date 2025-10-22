<?php

namespace App\Filament\Resources;

use App\Enums\ActiveStatusEnum;
use App\Enums\ManagerStatusEnum;
use App\Filament\Resources\ManagerResource\Pages;
use App\Filament\Resources\ManagerResource\RelationManagers;
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

    protected static ?string $navigationIcon = 'heroicon-o-user-group';

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
        return __('ui.report_management');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where(function ($query) {

//            $query
//                ->where('is_manager', true)
//                ->where('id', '!=', Auth::id())
//                ->where('id', '>', 1); // exclude super admin user with ID 1

            if (auth()->user()?->hasRole('super_admin') || auth()->user()?->can('view_all_managers')
            ) {
                return $query;
            }

            return $query->where('created_by', auth()->id());
        });
    }

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                \Filament\Forms\Components\Card::make()
                    ->schema([
                        Fieldset::make(__('ui.manager_information'))
                            ->columns(1)
                            ->schema([
                                Forms\Components\Select::make('user_id')
                                    ->label(__('ui.manager'))
                                    ->searchable()
                                    ->preload()
                                    ->options(function (callable $get, ?Manager $record = null) {
                                        $existingUserIds = Manager::query()
                                            ->when($record, function ($query) use ($record) {
                                                return $query->where('id', '!=', $record->id);
                                            })
                                            ->pluck('user_id')
                                            ->toArray();

                                        $query = \App\Models\User::query()
                                            //->where('is_manager', true)
                                            ->where('id', '!=', Auth::id())
                                            ->where('status', ManagerStatusEnum::ACTIVE);

                                        if (!auth()->user()?->hasRole('super_admin') && !auth()->user()?->can('view_all_managers')) {
                                            $query->where('created_by', auth()->id());
                                        }

                                        $users = $query->whereNotIn('id', $existingUserIds)
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
                Tables\Columns\TextColumn::make('user.tc_no')
                    ->visible(fn ($record) => auth()->user()->hasRole('super_admin') || auth()->user()->can('view_tc_no'))
                    ->label(__('ui.tc_no'))
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('user.name')
                    ->label(__('ui.full_name'))
                    ->badge()
                    ->color('primary')
                    ->searchable(),
                Tables\Columns\TextColumn::make('user.report.department_name')
                    ->label(__('ui.department'))
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('user.report.position_name')
                    ->label(__('ui.position'))
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
//                Tables\Columns\TextColumn::make('staffs_count')
//                    ->label(__('ui.staffs_count'))
//                    ->counts('staffs')
//                    ->badge()
//                    ->color('info')
//                    //->alignCenter()
//                    ->sortable(),
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
                    ->dateTime()
                    ->sortable()
                    ->toggleable()
                    ->toggleable(isToggledHiddenByDefault: true),
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
                        Tables\Actions\DeleteAction::make(),
                            /*
                            ->requiresConfirmation()
                            ->action(function ($record) {
                                DB::transaction(function () use ($record) {
                                    try {
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
                        */
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
            //
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
