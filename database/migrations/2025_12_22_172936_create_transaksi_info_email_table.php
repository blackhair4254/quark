<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('transaksi_info_email', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_transaksi_h')->unique();
            $table->boolean('status_info_email')->default(false);
            $table->timestamps();

            $table->foreign('id_transaksi_h')
                  ->references('id_transaksi')
                  ->on('transaksi_h')
                  ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transaksi_info_email');
    }
};
