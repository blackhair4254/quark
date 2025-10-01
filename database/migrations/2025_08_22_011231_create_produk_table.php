<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

    public function up(): void
    {
        Schema::create('produk', function (Blueprint $table) {
            $table->id('id_produk');
            $table->string('chain_link')->index();
            $table->string('nama_produk');
            $table->string('sku')->nullable();
            $table->string('category');
            $table->text('deskripsi');
            $table->decimal('berat',10,2)->default(0);
            $table->string('dimensi_barang')->nullable();
            $table->string('foto');
            $table->string('harga_beli',14,2)->default(0);
            $table->string('harga_jual',14,2)->default(0);
            $table->timestamps();

            $table->unique(['chain_link','sku']);
        });
    }


    public function down(): void
    {
        Schema::dropIfExists('produk');
    }
};
