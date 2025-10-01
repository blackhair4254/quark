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
        Schema::create('stock', function (Blueprint $table) {
            $table->unsignedBigInteger('id_produk')->primary();
            $table->string('chain_link')->index();
            $table->integer('qty')->default(0);
            $table->timestamps();

            $table->foreign('id_produk')
                ->references('id_produk')->on('produk')
                ->onDelete('cascade');
        });

        DB::statement('ALTER TABLE stock ADD CONSTRAINT stock_qty_nonneg CHECK (qty >= 0)');

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock');
    }
};
