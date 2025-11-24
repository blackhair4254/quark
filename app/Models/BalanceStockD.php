<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BalanceStockD extends Model
{
    protected $table = 'balance_stock_d';

    protected $fillable = [
        'id_adjustment',
        'id_produk',
        'qty_system',
        'qty_fisik',
        'selisih',
        'tipe_selisih',
        'keterangan',
    ];

    public function header()
    {
        return $this->belongsTo(BalanceStockH::class, 'id_adjustment', 'id_adjustment');
    }

    public function produk()
    {
        return $this->belongsTo(Produk::class, 'id_produk', 'id_produk');
    }
}
