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
        Schema::create('inbound_d', function (Blueprint $table) {
            $table->unsignedBigInteger('id_inbound_h');
            $table->unsignedBigInteger('id_produk');
            $table->integer('qty');                         // * (>0)
            $table->timestamps();

            $table->primary(['id_inbound_h', 'id_produk']);

            $table->foreign('id_inbound_h')
                  ->references('id_inbound')->on('inbound_h')
                  ->onDelete('cascade');

            $table->foreign('id_produk')
                  ->references('id_produk')->on('produk')
                  ->onDelete('restrict');
        });

        DB::statement('ALTER TABLE inbound_d ADD CONSTRAINT inbound_d_qty_pos CHECK (qty > 0)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inbound_d');
    }
};
