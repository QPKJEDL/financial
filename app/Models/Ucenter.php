<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Ucenter extends Model
{
    //

    protected $table="users";
    protected $primaryKey = 'uid';
    protected $fillable = ['username', 'mobile', 'password'];
    protected $hidden = ['password', 'remember_token'];
}
