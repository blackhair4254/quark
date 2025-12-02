<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TransaksiH extends Model
{
    protected $table = 'transaksi_h';
    protected $primaryKey = 'id_transaksi';

    protected $fillable = [
        'chain_link','status','invoice','pengirim','no_telp_pengirim',
        'jenis_logistik','no_resi','nama_penerima','no_telp_penerima',
        'alamat_penerima','tanggal',
        // Shopee fields
        'shopee_order_sn','shopee_region','shopee_currency','shopee_cod',
        'shopee_order_status','shopee_buyer_user_id','shopee_buyer_username',
        'shopee_shipping_carrier','shopee_payment_method',
        'shopee_total_amount','shopee_estimated_shipping_fee','shopee_actual_shipping_fee',
        'shopee_reverse_shipping_fee','shopee_days_to_ship',
        'shopee_order_chargeable_weight_gram',
        'shopee_create_time','shopee_update_time','shopee_pay_time',
        'shopee_ship_by_date','shopee_return_request_due_date',
        'shopee_is_buyer_shop_collection','shopee_goods_to_declare',
        'shopee_fulfillment_flag','shopee_message_to_seller','shopee_note',
        'shopee_note_update_time','shopee_pending_terms','shopee_recipient_address',
        'shopee_package_list','shopee_invoice_data','shopee_payment_info','shopee_raw',
    ];

    protected $casts = [
        'pending_payload' => 'array',
        'tanggal' => 'date',
        'shopee_cod' => 'boolean',
        'shopee_is_buyer_shop_collection' => 'boolean',
        'shopee_goods_to_declare' => 'boolean',
        'shopee_create_time' => 'datetime',
        'shopee_update_time' => 'datetime',
        'shopee_pay_time' => 'datetime',
        'shopee_ship_by_date' => 'datetime',
        'shopee_return_request_due_date' => 'datetime',
        'shopee_pending_terms' => 'array',
        'shopee_recipient_address' => 'array',
        'shopee_package_list' => 'array',
        'shopee_invoice_data' => 'array',
        'shopee_payment_info' => 'array',
        'shopee_raw' => 'array',
    ];

    public function details()
    {
        return $this->hasMany(TransaksiD::class, 'id_transaksi_h', 'id_transaksi');
    }
}
