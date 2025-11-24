<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('balance_stock_d', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_adjustment');
            $table->unsignedBigInteger('id_produk');

            $table->integer('qty_system');         // snapshot stok sistem saat create
            $table->integer('qty_fisik');          // hasil opnam
            $table->integer('selisih');            // qty_fisik - qty_system
            $table->enum('tipe_selisih', ['lebih','kurang','sama']);

            $table->text('keterangan')->nullable();

            $table->timestamps();

            $table->foreign('id_adjustment')
                ->references('id_adjustment')->on('balance_stock_h')
                ->onDelete('cascade');

            $table->foreign('id_produk')
                ->references('id_produk')->on('produk')
                ->onDelete('restrict');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('balance_stock_d');
    }
};
