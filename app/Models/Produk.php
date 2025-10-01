<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Produk extends Model
{
    protected $table = 'produk';
    protected $primaryKey = 'id_produk';
    protected $fillable = [
        'chain_link','nama_produk','sku','category','deskripsi',
        'berat','dimensi_barang','foto','harga_beli','harga_jual'
    ];

    public function stock()     { return $this->hasOne(Stock::class, 'id_produk', 'id_produk'); }
    public function inboundDs() { return $this->hasMany(InboundD::class, 'id_produk', 'id_produk'); }
    public function transaksiDs(){ return $this->hasMany(TransaksiD::class, 'id_produk', 'id_produk'); }

    protected static function booted() {
        static::created(function ($produk) {
            $produk->stock()->create([
                'chain_link' => $produk->chain_link,
                'qty'        => 0,
            ]);
        });
    }

}

