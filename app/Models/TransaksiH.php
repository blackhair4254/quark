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
        'alamat_penerima','tanggal'
    ];
    protected $casts = [
        'pending_payload' => 'array',
        'tanggal' => 'date',
    ];

    public function details() { return $this->hasMany(TransaksiD::class, 'id_transaksi_h', 'id_transaksi'); }
}
