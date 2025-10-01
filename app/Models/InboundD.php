<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InboundD extends Model
{
    protected $table = 'inbound_d';
    public $timestamps = false;
    public $incrementing = false; // pakai PK komposit
    protected $fillable = ['id_inbound_h','id_produk','qty'];

    public function header() { 
        return $this->belongsTo(InboundH::class, 'id_inbound_h', 'id_inbound'); 
    }
    public function produk() { 
        return $this->belongsTo(Produk::class, 'id_produk', 'id_produk'); 
    }
}
