<?php

namespace App\Filament\Exports;

use App\Models\Staff;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;

class StaffExporter extends Exporter
{
    protected static ?string $model = Staff::class;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where(function ($query) {
            if (auth()->user()?->hasRole('super_admin') || auth()->user()?->can('view_all_staff')
            ) {
                return $query;
            }
            return $query->where('manager_id', function ($subQuery) {
                $subQuery->select('id')
                    ->from('managers')
                    ->where('user_id', auth()->id());
            });
        });
    }

    public static function getColumns(): array
    {
        $isSuperAdmin = auth()->user()->hasRole('super_admin');
        $canViewTcNo = auth()->user()->can('view_tc_no');
        $canViewAllStaff = auth()->user()->can('view_all_staff');

        $columns = [];

        if ($isSuperAdmin || $canViewTcNo) {
            $columns[] = ExportColumn::make('employee.tc_no')
                ->label(__('ui.tc_no'));
        }

        $columns = array_merge($columns, [
            ExportColumn::make('employee.full_name')
                ->label(__('ui.full_name')),
            ExportColumn::make('employee.latestReport.department_name')
                ->label(__('ui.department')),
            ExportColumn::make('employee.latestReport.position_name')
                ->label(__('ui.position')),
        ]);

        if ($isSuperAdmin || $canViewAllStaff) {
            $columns[] = ExportColumn::make('manager.user.name')
                ->label(__('ui.manager'));
        }

        return $columns;
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
//        $body = 'Your staff export has completed and ' . number_format($export->successful_rows) . ' ' . str('row')->plural($export->successful_rows) . ' exported.';
//
//        if ($failedRowsCount = $export->getFailedRowsCount()) {
//            $body .= ' ' . number_format($failedRowsCount) . ' ' . str('row')->plural($failedRowsCount) . ' failed to export.';
//        }
//
//        return $body;

        $rows = number_format($export->successful_rows);
        $body = $rows . ' veri dışa aktarılmaya hazır.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $failedRows = number_format($failedRowsCount);
            $body .= ' ' . $failedRows . ' veri dışa aktarılamadı.';
        }

        return $body;
    }
}
