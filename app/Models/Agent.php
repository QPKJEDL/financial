<?php


namespace App\Models;


use App\Models\Traits\AdminUsersTrait;
use Illuminate\Auth\Authenticatable;
use Illuminate\Auth\Passwords\CanResetPassword;
use Illuminate\Database\Eloquent\Model;

class Agent extends Model
{
    use Authenticatable, CanResetPassword, AdminUsersTrait;
    protected $table = 'agent_users';
    protected $fillable = ['username', 'email', 'mobile', 'password'];
    protected $hidden = ['password', 'remember_token'];
}