<?php

namespace App\Models;

use App\Enums\ManagerStatusEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;

class Report extends Model
{
    use Notifiable;
    protected $fillable = [
        'tc_no',
        'full_name',
        'department_name',
        'position_name',
        'date',
        'day',
        'first_reading',
        'last_reading',
        'working_time',
        'status',
    ];

    protected $casts = [
        'status' => ManagerStatusEnum::class,
    ];

    // Relation with Employee model
    public function employee()
    {
        return $this->belongsTo(Employee::class, 'tc_no', 'tc_no');
    }
}
