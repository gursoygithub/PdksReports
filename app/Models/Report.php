<?php

namespace App\Models;

use App\Enums\ManagerStatusEnum;
use Illuminate\Database\Eloquent\Model;

class Report extends Model
{
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
}
