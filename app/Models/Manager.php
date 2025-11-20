<?php

namespace App\Models;

use App\Enums\ActiveStatusEnum;
use App\Enums\ManagerStatusEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes;

class Manager extends Model
{
    use Notifiable, SoftDeletes;

    protected $fillable = [
        'employee_id',
        'status',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $casts = [
        'status' => ManagerStatusEnum::class,
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id', 'id')->with(['reports' => function ($query) {
            $query->orderBy('date', 'desc');
        }]);
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'employee_id', 'employee_id');
    }

    public function staffs()
    {
        return $this->hasMany(Staff::class);
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
