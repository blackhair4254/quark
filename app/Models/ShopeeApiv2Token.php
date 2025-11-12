<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShopeeApiv2Token extends Model
{
    protected $fillable = [
        'chain_link',
        'partner_id',
        'shop_id',
        'access_token',
        'refresh_token',
        'expire_in',
        'request_id',
        'error',
        'message',
        'access_expires_at',
        'updated_at',
    ];


    protected $casts = [
        'access_expires_at' => 'datetime',
    ];
}
