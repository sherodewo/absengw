<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Notifications\ResetPassword as ResetPasswordNotification;
use App\Contracts\Model as ModelContracts;
use Laravel\Passport\HasApiTokens;

class User extends Authenticatable implements ModelContracts
{
    use HasApiTokens, Notifiable, SoftDeletes;

    public $table = 'users';

    protected $dates = ['created_at', 'updated_at', 'deleted_at'];

    protected $fillable = ['name', 'email', 'password', 'avatar', 'active'];

    protected $hidden = ['password', 'is_admin', 'remember_token'];

    protected $casts = ['is_admin' => 'boolean'];

    public function sql()
    {
        $user_role = new UserRole;
        $role = new Role;
        return $this
            ->leftJoin($user_role->table, $user_role->table.'.user_id', '=', $this->table.'.id')
            ->leftJoin($role->table, $role->table.'.id', '=', $user_role->table.'.role_id')
            ->select(
                $this->table.'.id',
                $role->table.'.name AS role',
                $this->table.'.name',
                $this->table.'.email',
                $this->table.'.avatar',
                \DB::raw('to_char('.$this->table.'.created_at, \'dd-mm-YYYY HH24:MI\') AS join_date'),
                \DB::raw('CASE '.$this->table.'.active WHEN TRUE THEN \'Active\' ELSE \'Not Active\' END AS active'),
                $this->table.'.active AS active_id'
            )->orderBy(
                $this->table.'.name'
            );
    }

    public function user_role()
    {
        return $this->hasOne(UserRole::class);
    }

    /**
     * Send the password reset notification.
     *
     * @param  string  $token
     * @return void
     */
    public function sendPasswordResetNotification($token)
    {
        $this->notify(new ResetPasswordNotification($token, $this->getEmailForPasswordReset()));
    }
}
