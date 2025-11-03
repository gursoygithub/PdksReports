<?php

namespace App\Filament\Resources;

use App\Enums\ManagerStatusEnum;
use App\Filament\Resources\ReportResource\Pages;
use App\Filament\Resources\ReportResource\RelationManagers;
use App\Models\Report;
use App\Models\Staff;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;

class ReportResource extends Resource
{
    protected static ?string $model = Report::class;

    protected static ?string $navigationIcon = 'heroicon-o-identification';

    public static function getModelLabel(): string
    {
        return __('ui.card_reading_report');
    }

    public static function getPluralModelLabel(): string
    {
        return __('ui.card_reading_reports');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('ui.report_management');
    }

    public static function getNavigationBadge(): ?string
    {
        $user = auth()->user();

        if ($user->hasRole('super_admin') || $user->can('view_all_reports')) {
            return static::getModel()::count();
            //return static::getModel()::where('status', ManagerStatusEnum::ACTIVE)->count();
        }

        // Get staff IDs by their manager's user ID
        $staffIds = Staff::whereIn('manager_id', function ($query) use ($user) {
            $query->select('id')
                ->from('managers')
                ->where('user_id', $user->id);
        })->pluck('id')->toArray();

        return static::getModel()::whereIn('id', function ($query) use ($staffIds) {
            $query->select('report_id')
                ->from('staff')
                ->whereIn('id', $staffIds);
        })->count();
    }

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        $user = auth()->user();

        if ($user->hasRole('super_admin') || $user->can('view_all_reports')) {
            return parent::getEloquentQuery();
        }

        // Get staff IDs by their manager's user ID
        $staffIds = Staff::whereIn('manager_id', function ($query) use ($user) {
            $query->select('id')
                ->from('managers')
                ->where('user_id', $user->id);
        })->pluck('id')->toArray();

        return parent::getEloquentQuery()->whereIn('id', function ($query) use ($staffIds) {
            $query->select('report_id')
                ->from('staff')
                ->whereIn('id', $staffIds);
        });
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
            ->defaultSort('full_name')
            ->modifyQueryUsing(fn (Builder $query) => $query->orderBy('full_name')->orderBy('date', 'desc'))
            ->paginated([5, 10, 25, 50])
            ->columns([
                Tables\Columns\TextColumn::make('tc_no')
                    ->visible(fn ($record) => auth()->user()->hasRole('super_admin') || auth()->user()->can('view_tc_no'))
                    ->label(__('ui.tc_no'))
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('full_name')
                    ->label(__('ui.full_name'))
                    ->badge()
                    ->color('primary')
                    ->searchable(),
                Tables\Columns\TextColumn::make('department_name')
                    ->label(__('ui.department'))
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('position_name')
                    ->label(__('ui.position'))
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('date')
                    ->label(__('ui.date'))
                    ->badge()
                    ->color('primary')
                    ->date(),
                Tables\Columns\TextColumn::make('first_reading')
                    ->label(__('ui.first_reading'))
                    ->badge()
                    ->color('success')
                    ->dateTime(),
                Tables\Columns\TextColumn::make('last_reading')
                    ->label(__('ui.last_reading'))
                    ->badge()
                    ->color('success')
                    ->dateTime(),
                Tables\Columns\TextColumn::make('working_time')
                    ->label(__('ui.working_time'))
                    ->badge()
                    ->color('success'),
                Tables\Columns\TextColumn::make('status')
                    ->label(__('ui.status'))
                    ->badge(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('department_name')
                    ->label(__('ui.department'))
                    ->preload()
                    ->searchable()
                    ->options(fn () => Report::query()
                        ->distinct()
                        ->pluck('department_name', 'department_name')
                        ->toArray()
                    ),
                Tables\Filters\Filter::make('today')
                    ->label(__('ui.today'))
                    ->query(fn (Builder $query): Builder => $query->whereDate('date', today()))
                    ->default(),
                Tables\Filters\Filter::make('date_range')
                    ->label(__('ui.date_range'))
                    ->form([
                        Forms\Components\DatePicker::make('from')
                            ->label(__('ui.from_date')),
                        Forms\Components\DatePicker::make('to')
                            ->label(__('ui.to_date')),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('date', '>=', $date),
                            )
                            ->when(
                                $data['to'],
                                fn (Builder $query, $date): Builder => $query->whereDate('date', '<=', $date),
                            );
                    }),
            ])
            ->headerActions([
                Tables\Actions\ExportAction::make()
                    ->exporter(\App\Filament\Exports\ReportExporter::class)
                    ->label(__('ui.export'))
                    ->modalHeading(__('ui.export_reports'))
                    ->icon('heroicon-o-arrow-down-tray')
                    ->visible(fn () => auth()->user()->hasRole('super_admin') || auth()->user()->can('export_reports')),
            ])
            ->actions([
                //
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
            'index' => Pages\ListReports::route('/'),
            'create' => Pages\CreateReport::route('/create'),
            'edit' => Pages\EditReport::route('/{record}/edit'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return false;
    }
}
