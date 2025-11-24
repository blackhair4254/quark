<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('balance_stock_h', function (Blueprint $table) {
            $table->id('id_adjustment');
            $table->string('kode_adjustment')->unique();
            $table->string('chain_link')->index();      // untuk multi tenant
            $table->string('gudang')->nullable();                   // nama / kode gudang
            $table->enum('status', ['submitted','approved','rejected'])->default('submitted');

            $table->unsignedBigInteger('created_by');   // id user OMS
            $table->unsignedBigInteger('approved_by')->nullable(); // id user WMS

            $table->timestamp('approved_at')->nullable();

            $table->timestamps();

            $table->index(['chain_link','status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('balance_stock_h');
    }
};
