<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BalanceStockH extends Model
{
    protected $table = 'balance_stock_h';
    protected $primaryKey = 'id_adjustment';

    protected $fillable = [
        'kode_adjustment',
        'chain_link',
        'gudang',
        'status',
        'created_by',
        'approved_by',
        'approved_at',
    ];

    protected $casts = [
        'approved_at' => 'datetime',
    ];

    public function details()
    {
        return $this->hasMany(BalanceStockD::class, 'id_adjustment', 'id_adjustment');
    }

    public function scopeForChain($query, string $chainLink)
    {
        return $query->where('chain_link', $chainLink);
    }
    public function creator()
    {
        return $this->belongsTo(\App\Models\Account::class, 'created_by', 'id_account');
    }

    public function approver()
    {
        return $this->belongsTo(\App\Models\Account::class, 'approved_by', 'id_account');
    }

}
