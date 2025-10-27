<?php

namespace App\Models;

use App\Enums\BooleanStatusEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes;

class Staff extends Model
{
    use Notifiable, SoftDeletes;

    protected $fillable = [
        'manager_id',
        'report_id',
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

    public function report()
    {
        return $this->belongsTo(Report::class);
    }

    public function reports()
    {
        return $this->hasMany(Report::class, 'id', 'report_id');
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
