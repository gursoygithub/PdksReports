<?php

namespace App\Models;

use App\Enums\BooleanStatusEnum;
use App\Enums\ManagerStatusEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes;

class Staff extends Model
{
    use Notifiable, SoftDeletes;

    protected $fillable = [
        'manager_id',
        'employee_id',
        'is_mailable',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $casts = [
        'is_mailable' => BooleanStatusEnum::class,
    ];

    public function manager()
    {
        return $this->belongsTo(Manager::class);
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class)->with(['reports' => function ($query) {
            $query->orderBy('date', 'desc');
        }]);
    }

    // Get all active reports associated with this staff through the employee
    public function reports()
    {
        return $this->hasManyThrough(
            Report::class,
            Employee::class,
            'id',       // Employee.id
            'tc_no',    // Report.tc_no
            'employee_id', // Staff.employee_id
            'tc_no'     // Employee.tc_no
        )->where('reports.status', ManagerStatusEnum::ACTIVE);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function deletedBy()
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }
}
