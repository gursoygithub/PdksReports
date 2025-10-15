<?php

namespace App\Filament\Exports;

use App\Models\Report;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;

class ReportExporter extends Exporter
{
    protected static ?string $model = Report::class;

    public static function getColumns(): array
    {
        $isSuperAdmin = auth()->user()->hasRole('super_admin');
        $canViewTcNo = auth()->user()->can('view_tc_no');

        $columns = [];

        // TC No sütunu sadece süper admin veya ilgili izne sahip kullanıcılar için gösterilir
        // ve dizinin başına eklenir
        if ($isSuperAdmin || $canViewTcNo) {
            $columns[] = ExportColumn::make('tc_no')
                ->label(__('ui.tc_no'));
        }

        // Diğer sütunlar
        $columns = array_merge($columns, [
            ExportColumn::make('full_name')
                ->label(__('ui.full_name')),
            ExportColumn::make('department_name')
                ->label(__('ui.department')),
            ExportColumn::make('position_name')
                ->label(__('ui.position')),
            ExportColumn::make('date')
                ->label(__('ui.date'))
                ->formatStateUsing(fn ($state) => date('d.m.Y', strtotime($state))),
            ExportColumn::make('first_reading')
                ->label(__('ui.first_reading'))
                ->formatStateUsing(fn ($state) => $state ? date('H:i:s', strtotime($state)) : ''),
            ExportColumn::make('last_reading')
                ->label(__('ui.last_reading'))
                ->formatStateUsing(fn ($state) => $state ? date('H:i:s', strtotime($state)) : ''),
            ExportColumn::make('working_time')
                ->label(__('ui.working_time')),
        ]);

        return $columns;
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
//        $body = 'Your report export has completed and ' . number_format($export->successful_rows) . ' ' . str('row')->plural($export->successful_rows) . ' exported.';
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
