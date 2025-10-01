<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('inbound_h', function (Blueprint $table) {
            $table->id('id_inbound');
            $table->string('chain_link')->index();
            $table->string('no_resi')->nullable();
            $table->string('status', 24)->default('draft');
            $table->text('deskripsi')->nullable();
            $table->date('tanggal_inbound');
            $table->integer('total_qty')->default(0);
            $table->integer('total_barang')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inbound_h');
    }
};
