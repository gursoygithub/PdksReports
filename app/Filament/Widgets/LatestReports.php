<?php

namespace App\Filament\Widgets;

use App\Enums\ManagerStatusEnum;
use Carbon\Carbon;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Contracts\Support\Htmlable;

class LatestReports extends BaseWidget
{
    //protected static ?string $heading = 'Son 10 Kart Okumta Listesi';

//    protected function getTableDescription(): string|Htmlable|null
//    {
//        return __('ui.list_of_the_last_10_card_reading_reports');
//    }

    /**
     * @return array|int|string
     */
    public function getColumnSpan(): int|array|string
    {
        return 'full';
    }

    protected static ?int $sort = 200;

    public function table(Table $table): Table
    {
        return $table
            ->query(function () {
                $hasPermissionToViewAll = auth()->user()?->hasRole('super_admin') || auth()->user()?->can('view_all_reports');

                $query = \App\Models\Report::query()
                    ->whereNotNull('first_reading')
                    ->whereNotNull('last_reading')
                    ->where('status', '==', ManagerStatusEnum::ACTIVE)
                    ->orderByDesc('last_reading')
                    ->limit(10);

                if (!$hasPermissionToViewAll) {
                    $manager = \App\Models\Manager::where('employee_id', auth()->user()->employee_id)->first();
                    if ($manager) {
                        $employeeIds = \App\Models\Staff::where('manager_id', $manager->id)->pluck('employee_id');
                        $tcNos = \App\Models\Employee::whereIn('id', $employeeIds)
                            ->where('status', ManagerStatusEnum::ACTIVE)
                            ->pluck('tc_no');
                        $query->whereIn('tc_no', $tcNos);
                    } else {
                        // If the user is not a manager, return no records
                        $query->whereRaw('1 = 0');
                    }
                }

                return $query;
            })
            ->heading(__('ui.card_reading_list'))
            ->description(__('ui.last_10_records'))
            ->paginated(false)
            ->columns([
                Tables\Columns\TextColumn::make('tc_no')
                    ->visible(fn ($record) => auth()->user()->hasRole('super_admin') || auth()->user()->can('view_tc_no'))
                    ->label(__('ui.tc_no'))
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('full_name')
                    ->label(__('ui.full_name'))
                    ->badge()
                    ->color('primary'),
                Tables\Columns\TextColumn::make('department_name')
                    ->label(__('ui.department'))
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('position_name')
                    ->label(__('ui.position'))
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('date')
                    ->label(__('ui.date'))
                    ->badge()
                    ->color('primary')
                    ->date(),
                Tables\Columns\TextColumn::make('day')
                    ->label(__('ui.day'))
                    ->badge()
                    ->color('primary')
                    ->formatStateUsing(fn ($state, $record) => Carbon::parse($record->date)->translatedFormat('l')),
                Tables\Columns\TextColumn::make('first_reading')
                    ->label(__('ui.first_reading'))
                    ->badge()
                    ->color('success')
                    ->time(),
                Tables\Columns\TextColumn::make('last_reading')
                    ->label(__('ui.last_reading'))
                    ->badge()
                    ->color('success')
                    ->time(),
                Tables\Columns\TextColumn::make('working_time')
                    ->label(__('ui.working_time'))
                    ->badge()
                    ->color('success'),
            ]);
    }
}
