<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('produk_marketplace_maps', function (Blueprint $table) {
            $table->id();
            $table->string('chain_link')->index();          // multi-tenant
            $table->string('marketplace', 32)->index();     // contoh: 'shopee'
            $table->unsignedBigInteger('shop_id')->nullable()->index();

            // identifier dari marketplace
            $table->unsignedBigInteger('marketplace_item_id');      // item_id dari shopee
            $table->unsignedBigInteger('marketplace_model_id')->nullable(); // model_id (boleh null utk non-varian)

            // produk internal
            $table->unsignedBigInteger('id_produk');        // FK ke produk.id_produk

            $table->timestamps();

            $table->foreign('id_produk')
                ->references('id_produk')->on('produk')
                ->onDelete('cascade');

            // Satu varian marketplace hanya boleh map ke satu produk internal
            $table->unique(
                ['chain_link', 'marketplace', 'shop_id', 'marketplace_item_id', 'marketplace_model_id'],
                'uniq_marketplace_variant'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('produk_marketplace_maps');
    }
};
