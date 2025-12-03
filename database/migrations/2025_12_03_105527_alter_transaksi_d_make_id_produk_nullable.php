<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 1) Drop foreign key & primary key lama
        Schema::table('transaksi_d', function (Blueprint $t) {
            // sesuaikan nama FK kalau di DB beda
            $t->dropForeign(['id_produk']);
            $t->dropPrimary(); // karena primary(['id_transaksi_h','id_produk']);
        });

        // 2) Tambah kolom id auto increment sebagai PK baru
        Schema::table('transaksi_d', function (Blueprint $t) {
            // akan jadi PK baru
            $t->bigIncrements('id')->first();
        });

        // 3) Jadikan id_produk nullable + tambahkan FK lagi (opsional)
        Schema::table('transaksi_d', function (Blueprint $t) {
            // butuh doctrine/dbal untuk ->change(), kalau belum ada bisa pakai DB::statement
            $t->unsignedBigInteger('id_produk')->nullable()->change();

            // kalau mau tetap ada FK tapi boleh null
            $t->foreign('id_produk')
                ->references('id_produk')->on('produk')
                ->nullOnDelete(); // atau ->onDelete('set null') tergantung DB
        });

        // (constraint qty_pos tetap aman, nggak diutak-atik)
    }

    public function down(): void
    {
        // Balik ke kondisi lama (PK komposit, id_produk NOT NULL)
        Schema::table('transaksi_d', function (Blueprint $t) {
            $t->dropForeign(['id_produk']);
            $t->dropPrimary();      // drop PK di kolom id
        });

        Schema::table('transaksi_d', function (Blueprint $t) {
            $t->dropColumn('id');
            $t->unsignedBigInteger('id_produk')->nullable(false)->change();
        });

        Schema::table('transaksi_d', function (Blueprint $t) {
            $t->primary(['id_transaksi_h', 'id_produk']);
            $t->foreign('id_produk')
                ->references('id_produk')->on('produk')
                ->onDelete('restrict');
        });
    }
};
