<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TransaksiD extends Model
{
    protected $table = 'transaksi_d';

    protected $primaryKey = 'id';
    public $incrementing  = true;
    protected $keyType    = 'int';

    protected $fillable = [
        'id_transaksi_h','id_produk','nama_produk','qty',
        // Shopee fields
        'shopee_item_id','shopee_order_item_id','shopee_model_id',
        'shopee_item_sku','shopee_model_sku','shopee_item_name','shopee_model_name',
        'shopee_model_original_price','shopee_model_discounted_price',
        'shopee_weight','shopee_add_on_deal','shopee_add_on_deal_id',
        'shopee_main_item','shopee_promotion_type','shopee_promotion_id',
        'shopee_promotion_group_id','shopee_image_url',
        'shopee_product_location_id','shopee_item_raw',
    ];

    protected $casts = [
        'shopee_add_on_deal' => 'boolean',
        'shopee_main_item' => 'boolean',
        'shopee_product_location_id' => 'array',
        'shopee_item_raw' => 'array',
    ];

    public function header()
    {
        return $this->belongsTo(TransaksiH::class, 'id_transaksi_h', 'id_transaksi');
    }

    public function produk()
    {
        return $this->belongsTo(Produk::class, 'id_produk', 'id_produk')->withTrashed();
    }
}
