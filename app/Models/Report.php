<?php

namespace App\Models;

use App\Enums\ManagerStatusEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;

class Report extends Model
{
    use Notifiable;
    protected $fillable = [
        'employee_id',
        'tc_no',
        'full_name',
        'department_name',
        'position_name',
        'date',
        'day',
//        'first_reading',
//        'last_reading',
        'working_time',
        'status',
    ];

    protected $casts = [
        'status' => ManagerStatusEnum::class,
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id', 'id');
    }

    public function manager()
    {
        return $this->belongsTo(Manager::class, 'employee_id', 'employee_id');
    }
    public function staff()
    {
        return $this->belongsTo(Staff::class, 'employee_id', 'employee_id');
    }

    public static function query()
    {
        $hasPermission = auth()->user()->hasRole('super_admin') || auth()->user()->can('view_all_reports');

        if ($hasPermission) {
            return parent::query();
        } else {
            $manager = Manager::where('employee_id', auth()->user()->employee_id)->first();

            if (! $manager) {
                return parent::query()->whereRaw('1 = 0');
            } else {
                $employeeIds = Staff::where('manager_id', $manager->id)->pluck('employee_id');
                $tcNos = Employee::whereIn('id', $employeeIds)
                    ->where('status', ManagerStatusEnum::ACTIVE)
                    ->pluck('tc_no');

                return parent::query()->whereIn('tc_no', $tcNos);
            }
        }
    }
}
