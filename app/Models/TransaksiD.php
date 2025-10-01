<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TransaksiD extends Model
{
    protected $table = 'transaksi_d';
    public $incrementing = false; // PK komposit
    protected $fillable = ['id_transaksi_h','id_produk','nama_produk','qty'];

    public function header() { return $this->belongsTo(TransaksiH::class, 'id_transaksi_h', 'id_transaksi'); }
    public function produk() { return $this->belongsTo(Produk::class, 'id_produk', 'id_produk'); }
}
