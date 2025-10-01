<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InboundH extends Model
{
    protected $table = 'inbound_h';
    protected $primaryKey = 'id_inbound';

    protected $casts = [
        'tanggal_inbound' => 'datetime',   // <-- penting
    ];
    protected $fillable = [
        'chain_link',
        'no_resi',
        'status',
        'deskripsi',
        'tanggal_inbound',
        'total_qty',
        'total_barang'
    ];

    protected $dates = ['tanggal_inbound'];

    public function details() { return $this->hasMany(InboundD::class, 'id_inbound_h', 'id_inbound'); }
}
