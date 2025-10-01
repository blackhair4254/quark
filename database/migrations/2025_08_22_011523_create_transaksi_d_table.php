<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('transaksi_d', function (Blueprint $table) {
            $table->unsignedBigInteger('id_transaksi_h');
            $table->unsignedBigInteger('id_produk');
            $table->string('nama_produk');
            $table->integer('qty');
            $table->timestamps();

            $table->primary(['id_transaksi_h','id_produk']);

            $table->foreign('id_transaksi_h')
                ->references('id_transaksi')->on('transaksi_h')
                ->onDelete('cascade');

            $table->foreign('id_produk')
                ->references('id_produk')->on('produk')
                ->onDelete('restrict');

        });

        DB::statement('ALTER TABLE transaksi_d ADD CONSTRAINT transaksi_d_qty_pos CHECK (qty > 0)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transaksi_d');
    }
};
