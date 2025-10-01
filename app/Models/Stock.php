<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Stock extends Model
{
    protected $table = 'stock';
    protected $primaryKey = 'id_produk';
    public $incrementing = false;

    protected $fillable = ['id_produk','chain_link','qty'];

    public function produk() { return $this->belongsTo(Produk::class, 'id_produk', 'id_produk'); }
}
