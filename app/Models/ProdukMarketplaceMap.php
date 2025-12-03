<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProdukMarketplaceMap extends Model
{
    protected $table = 'produk_marketplace_maps';

    protected $fillable = [
        'chain_link',
        'marketplace',
        'shop_id',
        'marketplace_item_id',
        'marketplace_model_id',
        'id_produk',
    ];

    public function produk()
    {
        return $this->belongsTo(Produk::class, 'id_produk', 'id_produk')->withTrashed();
    }
}
