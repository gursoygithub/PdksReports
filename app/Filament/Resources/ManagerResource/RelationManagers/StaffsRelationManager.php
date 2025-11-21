<?php

namespace App\Filament\Resources\ManagerResource\RelationManagers;

use App\Enums\BooleanStatusEnum;
use App\Enums\ManagerStatusEnum;
use App\Models\Employee;
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
        return __('ui.staff_under_manager');
    }

    protected static function getPluralModelLabel(): ?string
    {
        return __('ui.staffs_under_manager');
    }

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('ui.staffs_under_manager');
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
                                Forms\Components\Select::make('employee_id')
                                        ->label(__('ui.staff'))
                                        ->searchable()
                                        ->preload()
                                        ->options(function (callable $get) {
                                            $manager = $this->ownerRecord;

                                            if ($manager && ! $manager->relationLoaded('user')) {
                                                $manager->load('user');
                                            }

                                            $managerEmployeeId = optional($manager->user)->employee_id;

                                            // Şu anda formda seçili olan çalışan id'sini al
                                            $currentEmployeeId = $get('employee_id');

                                            // Bu manager'a zaten eklenmiş staff'ları al
                                            $existingStaffIds = $manager->staffs()
                                                ->pluck('employee_id')
                                                ->toArray();

                                            return \App\Models\Employee::query()
                                                ->where('status', \App\Enums\ManagerStatusEnum::ACTIVE)
                                                // Eğer yeni kayıt ekleniyorsa sadece staff olmayanlar
                                                // Ama update yapılıyorsa mevcut seçili personel dahil edilmeli
                                                //->when(!$currentEmployeeId, fn($q) => $q->where('is_staff', \App\Enums\BooleanStatusEnum::NO))
                                                ->when($managerEmployeeId, fn($q) => $q->where('id', '!=', $managerEmployeeId))
                                                // Zaten bu manager'a eklenmiş olanları hariç tut (güncelleme durumunda mevcut kayıt hariç)
                                                ->whereNotIn('id', array_diff($existingStaffIds, [$currentEmployeeId]))
                                                ->orderBy('first_name')
                                                ->get()
                                                ->mapWithKeys(fn($employee) => [$employee->id => $employee->full_name])
                                                ->toArray();
                                        })
                                        //->unique(ignoreRecord: true)
                                        ->required()
                                        ->validationMessages([
                                            'required' => __('ui.required'),
                                            //'unique' => __('ui.staff_already_assigned_to_manager'),
                                        ])
                            ]),
                    ]),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->defaultSort('updated_at', 'desc')
            ->modifyQueryUsing(fn (Builder $query) => $query->orderBy('created_at', 'desc'))
            ->paginated([5, 10, 25, 50])
            ->columns([
                Tables\Columns\TextColumn::make('employee.tc_no')
                    ->visible(fn ($record) => auth()->user()->hasRole('super_admin') || auth()->user()->can('view_tc_no'))
                    ->label(__('ui.tc_no'))
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('employee.full_name')
                    ->label(__('ui.staff'))
                    ->badge()
                    ->color('primary')
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->whereHas('employee', function (Builder $query) use ($search) {
                            $query->where('first_name', 'like', "%{$search}%")
                                ->orWhere('last_name', 'like', "%{$search}%");
                        });
                    }),
                Tables\Columns\TextColumn::make('employee.latestReport.department_name')
                    ->label(__('ui.department'))
                    ->searchable()
                    ->placeholder('-'),
                Tables\Columns\TextColumn::make('employee.latestReport.position_name')
                    ->label(__('ui.position'))
                    ->searchable()
                    ->placeholder('-'),
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
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['created_by'] = auth()->id();
                        $data['manager_id'] = $this->ownerRecord->employee_id;

                        // Update the related employee to set is_staff to true
                        $employee = Employee::find($data['employee_id']);

                        if ($employee) {
                            $employee->is_staff = BooleanStatusEnum::YES;
                            $employee->save();
                        }

                        return $data;
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->mutateFormDataUsing(function (array $data, Model $record): array {

                        $data['updated_by'] = auth()->id();

                        // If employee_id is changed, update the is_staff flags accordingly
                        if (isset($data['employee_id']) && $data['employee_id'] != $record->employee_id) {
                            // Set old employee's is_staff to false
                            $oldEmployee = Employee::find($record->employee_id);
                            if ($oldEmployee) {
                                $oldEmployee->is_staff = BooleanStatusEnum::NO;
                                $oldEmployee->save();
                            }

                            // Set new employee's is_staff to true
                            $newEmployee = Employee::find($data['employee_id']);
                            if ($newEmployee) {
                                $newEmployee->is_staff = BooleanStatusEnum::YES;
                                $newEmployee->save();
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
                                // Set the related employee's is_staff to false
                                $employee = Employee::find($record->employee_id);
                                if ($employee) {
                                    $employee->is_staff = BooleanStatusEnum::NO;
                                    $employee->save();
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
