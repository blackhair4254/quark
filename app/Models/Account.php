<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;

class Account extends Authenticatable
{
    protected $table = 'account';
    protected $primaryKey = 'id_account';
    protected $fillable = [
        'nama_pengguna','email_pengguna','password','chain_link','role'
    ];
    protected $hidden = ['password'];
}
