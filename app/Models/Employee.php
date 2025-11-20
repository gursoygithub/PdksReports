<?php

namespace App\Models;

use App\Enums\BooleanStatusEnum;
use App\Enums\ManagerStatusEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notifiable;

class Employee extends Model
{
    use Notifiable, SoftDeletes;

    protected $fillable = [
        'tc_no',
        'first_name',
        'last_name',
        'status',
        'create_time',
        'update_time',
        'is_manager',
        'is_staff',
        'is_mailable',
        'deleted_by',
    ];

    protected $casts = [
        'status' => ManagerStatusEnum::class,
        'is_manager' => BooleanStatusEnum::class,
        'is_staff' => BooleanStatusEnum::class,
        'is_mailable' => BooleanStatusEnum::class,
    ];

    //relation with Report model
    public function reports()
    {
        return $this->hasMany(Report::class, 'employee_id', 'employee_id');
    }

    // Accessor for full name
    public function getFullNameAttribute(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }

    // Amaç: En son raporu alarak çalışanın en güncel bilgilerine erişmek, çünkü bazı sütunlar employee tablosunda değil, report tablosunda bulunuyor.
    // Örneğin: department_name, position_name gibi.
    public function latestReport()
    {
        return $this->hasOne(Report::class, 'employee_id', 'id')->latestOfMany();
    }

    //relation with Manager model
    public function manager()
    {
        return $this->hasOne(Manager::class, 'employee_id', 'id');
    }

    //relation with Staff model
    public function staffs()
    {
        return $this->hasMany(Staff::class, 'employee_id', 'id');
    }

    public static function query()
    {
        $hasPermission = auth()->user()->hasRole('super_admin') || auth()->user()->can('view_all_employees');

        if ($hasPermission) {
            return parent::query();
        } else {
            $manager = \App\Models\Manager::where('employee_id', auth()->user()->employee_id)->first();
            if (! $manager) {
                return parent::query()->whereRaw('1 = 0'); // No records
            } else {
                $employeeIds = \App\Models\Staff::where('manager_id', $manager->id)->pluck('employee_id');
                return parent::query()->whereIn('id', $employeeIds)
                    ->where('status', ManagerStatusEnum::ACTIVE);
            }
        }
    }

}
