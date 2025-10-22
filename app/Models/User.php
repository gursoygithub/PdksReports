<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\ManagerStatusEnum;
use App\Enums\UserTypeEnum;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Permission\Traits\HasRoles;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use App\Enums\ActiveStatusEnum;
class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;
    use SoftDeletes;
    use HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'tc_no',
        'name',
        'email',
        'phone',
        'status',
        'is_manager',
        'password',
        'project_id',
        'project_name',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'status' => ManagerStatusEnum::class,
            'is_manager' => UserTypeEnum::class,
        ];
    }

    // Relation with managers
    public function managers()
    {
        return $this->hasMany(Manager::class, 'user_id');
    }

    // Relation with reports
    public function report()
    {
        return $this->hasOne(Report::class, 'tc_no', 'tc_no');
    }

    public function staffs()
    {
        return $this->hasMany(Staff::class, 'user_id');
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->status->is(ManagerStatusEnum::ACTIVE);
    }
}
