<?php

namespace App\Filament\Exports;

use App\Enums\VisitorStatusEnum;
use App\Models\VisitorCard;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;

class VisitorCardExporter extends Exporter
{
    protected static ?string $model = VisitorCard::class;

    public static function getColumns(): array
    {
        $isSuperAdmin = auth()->user()->hasRole('super_admin');
        $columns = [
            ExportColumn::make('visitor.full_name')
                ->label(__('ui.visitor')),
            ExportColumn::make('personToVisit.name')
                ->label(__('ui.person_to_visit')),
            ExportColumn::make('card.number')
                ->label(__('ui.card_number')),
            ExportColumn::make('vehicles_count')
                ->label(__('ui.vehicle_count'))
                ->counts('vehicles'),
        ];

        // Süper admin kontrolü
        if ($isSuperAdmin) {
            $columns[] = ExportColumn::make('createdBy.project_name')
                ->label(__('ui.project'));
        }

        $columns[] = ExportColumn::make('status')
            ->label(__('ui.visitor_status'))
            ->formatStateUsing(fn (VisitorStatusEnum $state) => $state->getLabel());
        $columns[] = ExportColumn::make('visited_at')
            ->label(__('ui.inside_at'))
            ->formatStateUsing(fn ($state) => date('d.m.Y H:i:s', strtotime($state)));
        $columns[] = ExportColumn::make('left_at')
            ->label(__('ui.outside_at'))
            ->formatStateUsing(fn ($state) => $state ? date('d.m.Y H:i:s', strtotime($state)) : null);

        // Süper admin kontrolü
        if ($isSuperAdmin) {
            $columns[] = ExportColumn::make('createdBy.name')
                ->label(__('ui.created_by'));
        }

        $columns[] = ExportColumn::make('created_at')
            ->label(__('ui.created_at'))
            ->formatStateUsing(fn ($state) => date('d.m.Y H:i:s', strtotime($state)));
        $columns[] = ExportColumn::make('updatedBy.name')
            ->label(__('ui.updated_by'));
        $columns[] = ExportColumn::make('updated_at')
            ->label(__('ui.updated_at'))
            ->formatStateUsing(fn ($state) => date('d.m.Y H:i:s', strtotime($state)));

        return $columns;
    }

//    public static function getCompletedNotificationBody(Export $export): string
//    {
//        $body = 'Your visitor card export has completed and ' . number_format($export->successful_rows) . ' ' . str('row')->plural($export->successful_rows) . ' exported.';
//
//        if ($failedRowsCount = $export->getFailedRowsCount()) {
//            $body .= ' ' . number_format($failedRowsCount) . ' ' . str('row')->plural($failedRowsCount) . ' failed to export.';
//        }
//
//        return $body;
//    }
    public static function getCompletedNotificationBody(Export $export): string
    {
        $rows = number_format($export->successful_rows);
        $body = $rows . ' ziyaretçi kartı dışa aktarılmaya hazır.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $failedRows = number_format($failedRowsCount);
            $body .= ' ' . $failedRows . ' satır dışa aktarılamadı.';
        }

        return $body;
    }

}
