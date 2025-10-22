<?php

namespace App\Filament\Resources\UserResource\RelationManagers;

use App\Enums\ManagerStatusEnum;
use App\Models\Report;
use Filament\Forms;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\DB;

class StaffsRelationManager extends RelationManager
{
    protected static string $relationship = 'staffs';

    protected static function getModelLabel(): ?string
    {
        return __('ui.staff');
    }

    protected static function getPluralModelLabel(): ?string
    {
        return __('ui.staffs');
    }

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('ui.staffs');
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                \Filament\Forms\Components\Card::make()
                    ->schema([
                        Fieldset::make(__('ui.staff_information'))
                            ->columns(1)
                            ->schema([
                                Forms\Components\Select::make('report_id')
                                    ->label(__('ui.staff'))
                                    ->searchable()
                                    ->preload()
                                    ->options(function (callable $get, ?Model $record = null) {
                                        $existingTcNumbers = $this->ownerRecord->staffs()
                                            ->join('reports', 'staff.report_id', '=', 'reports.id')
                                            ->when($record, function ($query) use ($record) {
                                                return $query->where('staff.id', '!=', $record->id);
                                            })
                                            ->pluck('reports.tc_no')
                                            ->toArray();

                                        $query = Report::query()
                                            ->where('status', ManagerStatusEnum::ACTIVE)
                                            ->where('tc_no', '!=', $this->ownerRecord->tc_no)
                                            ->where('is_staff', 0);

                                        // Eğer düzenleme modundaysak, mevcut kaydın report_id'sine ait raporu da seçenekler arasında göster
                                        if ($record && $record->report_id) {
                                            $currentReport = Report::find($record->report_id);
                                            if ($currentReport) {
                                                $query->orWhere('id', $record->report_id);
                                            }
                                        }

                                        return $query->whereNotIn('tc_no', $existingTcNumbers)
                                            ->selectRaw('MIN(id) as id, full_name')
                                            ->groupBy('tc_no', 'full_name')
                                            ->pluck('full_name', 'id')
                                            ->toArray();
                                    })
                            ]),
                    ]),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('updated_at', 'desc')
            ->modifyQueryUsing(fn (Builder $query) => $query->orderBy('created_at', 'desc'))
            ->paginated([5, 10, 25, 50])
            ->columns([
                Tables\Columns\TextColumn::make('report.full_name')
                    ->label(__('ui.staff'))
                    ->badge()
                    ->color('primary')
                    ->searchable(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['created_by'] = auth()->id();
                        $data['user_id'] = $this->ownerRecord->id;

                        // Update the related report to set is_staff to true
                        $report = Report::find($data['report_id']);
                        if ($report) {
                            $report->is_staff = 1;
                            $report->save();
                        }

                        return $data;
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->mutateFormDataUsing(function (array $data, Model $record): array {

                        $data['updated_by'] = auth()->id();

                        // If report_id is changed, update the is_staff flags accordingly
                        if (isset($data['report_id']) && $data['report_id'] != $record->report_id) {
                            // Set old report's is_staff to false
                            $oldReport = Report::find($record->report_id);
                            if ($oldReport) {
                                $oldReport->is_staff = 0;
                                $oldReport->save();
                            }

                            // Set new report's is_staff to true
                            $newReport = Report::find($data['report_id']);
                            if ($newReport) {
                                $newReport->is_staff = 1;
                                $newReport->save();
                            }
                        }
                        return $data;
                    }),
                Tables\Actions\DeleteAction::make()
                    ->requiresConfirmation()
                    ->modalHeading(__('ui.delete_staff'))
                    ->action(function (Model $record) {
                        DB::transaction(function () use ($record) {
                            try {
                                // Set the related report's is_staff to false
                                $report = Report::find($record->report_id);
                                if ($report) {
                                    $report->is_staff = 0;
                                    $report->save();
                                }

                                // Soft delete the staff record
                                $record->deleted_by = auth()->id();
                                $record->deleted_at = now();
                                $record->save();

                                Notification::make()
                                    ->title(__('ui.deletion_successful'))
                                    ->success()
                                    ->send();
                            } catch (\Exception $e) {
                                DB::rollBack();
                                Notification::make()
                                    ->title(__('ui.deletion_failed'))
                                    ->danger()
                                    ->send();
                                throw $e;
                            }
                        });
                    }),
            ])
            ->bulkActions([
                //
            ]);
    }

    public function isReadOnly(): bool
    {
        return false;
    }
}
