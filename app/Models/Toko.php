<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Toko extends Model
{
    protected $table = 'toko';
    protected $primaryKey = 'id';
    public $timestamps = true;

    protected $fillable = [
        'chain_link',
        'nama_toko',
        'alamat',
        'kota',
        'provinsi',
        'negara',
        'kode_pos',
        'no_telp',
        'email',
        'website',
        'logo_path',
        'currency',
        'timezone',
        'invoice_prefix',
        'invoice_counter',
        'bank_name',
        'bank_account_no',
        'bank_account_name',
    ];
}
