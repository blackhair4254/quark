<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TransaksiInfoEmail extends Model
{
    protected $table = 'transaksi_info_email';
    protected $fillable = ['id_transaksi_h', 'status_info_email'];
}
