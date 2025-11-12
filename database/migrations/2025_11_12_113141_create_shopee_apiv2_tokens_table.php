<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('shopee_apiv2_tokens', function (Blueprint $table) {
            $table->id();

            $table->string('chain_link')->index();

            $table->text('access_token')->nullable();   
            $table->text('refresh_token')->nullable();  
            $table->integer('expire_in')->nullable();   
            $table->string('request_id', 64)->nullable(); 
            $table->string('error', 64)->nullable();      
            $table->text('message')->nullable();          

            $table->timestamp('access_expires_at')->nullable()->index();  // opsional: hitung dari now()+expire_in

            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
            $table->unique('chain_link');

        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shopee_apiv2_tokens');
    }
};
