<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transaksi_h', function (Blueprint $t) {
            // Info order Shopee (header)
            $t->string('shopee_order_sn')->nullable()->index();
            $t->string('shopee_region', 8)->nullable();
            $t->string('shopee_currency', 8)->nullable();
            $t->boolean('shopee_cod')->nullable();
            $t->string('shopee_order_status', 32)->nullable();

            $t->unsignedBigInteger('shopee_buyer_user_id')->nullable();
            $t->string('shopee_buyer_username')->nullable();

            $t->string('shopee_shipping_carrier')->nullable();
            $t->string('shopee_payment_method')->nullable();

            $t->decimal('shopee_total_amount', 18, 2)->nullable();
            $t->decimal('shopee_estimated_shipping_fee', 18, 2)->nullable();
            $t->decimal('shopee_actual_shipping_fee', 18, 2)->nullable();
            $t->decimal('shopee_reverse_shipping_fee', 18, 2)->nullable();

            $t->integer('shopee_days_to_ship')->nullable();
            $t->integer('shopee_order_chargeable_weight_gram')->nullable();

            $t->timestamp('shopee_create_time')->nullable();
            $t->timestamp('shopee_update_time')->nullable();
            $t->timestamp('shopee_pay_time')->nullable();
            $t->timestamp('shopee_ship_by_date')->nullable();
            $t->timestamp('shopee_return_request_due_date')->nullable();

            $t->boolean('shopee_is_buyer_shop_collection')->nullable();
            $t->boolean('shopee_goods_to_declare')->nullable();

            $t->string('shopee_fulfillment_flag', 64)->nullable();
            $t->text('shopee_message_to_seller')->nullable();
            $t->text('shopee_note')->nullable();
            $t->timestamp('shopee_note_update_time')->nullable();

            $t->json('shopee_pending_terms')->nullable();
            $t->json('shopee_recipient_address')->nullable();
            $t->json('shopee_package_list')->nullable();
            $t->json('shopee_invoice_data')->nullable();
            $t->json('shopee_payment_info')->nullable();
            $t->json('shopee_raw')->nullable(); // simpan raw 1 order Shopee
        });

        Schema::table('transaksi_d', function (Blueprint $t) {
            // Info item Shopee (detail)
            $t->unsignedBigInteger('shopee_item_id')->nullable()->index();
            $t->unsignedBigInteger('shopee_order_item_id')->nullable();
            $t->unsignedBigInteger('shopee_model_id')->nullable();

            $t->string('shopee_item_sku')->nullable();
            $t->string('shopee_model_sku')->nullable();
            $t->string('shopee_model_name')->nullable();

            $t->decimal('shopee_model_original_price', 18, 2)->nullable();
            $t->decimal('shopee_model_discounted_price', 18, 2)->nullable();
            $t->decimal('shopee_weight', 10, 3)->nullable();

            $t->boolean('shopee_add_on_deal')->nullable();
            $t->unsignedBigInteger('shopee_add_on_deal_id')->nullable();
            $t->boolean('shopee_main_item')->nullable();

            $t->string('shopee_promotion_type')->nullable();
            $t->unsignedBigInteger('shopee_promotion_id')->nullable();
            $t->integer('shopee_promotion_group_id')->nullable();

            $t->text('shopee_image_url')->nullable();
            $t->json('shopee_product_location_id')->nullable();
            $t->json('shopee_item_raw')->nullable(); // simpan raw item Shopee
        });
    }

    public function down(): void
    {
        Schema::table('transaksi_h', function (Blueprint $t) {
            $t->dropColumn([
                'shopee_order_sn',
                'shopee_region',
                'shopee_currency',
                'shopee_cod',
                'shopee_order_status',
                'shopee_buyer_user_id',
                'shopee_buyer_username',
                'shopee_shipping_carrier',
                'shopee_payment_method',
                'shopee_total_amount',
                'shopee_estimated_shipping_fee',
                'shopee_actual_shipping_fee',
                'shopee_reverse_shipping_fee',
                'shopee_days_to_ship',
                'shopee_order_chargeable_weight_gram',
                'shopee_create_time',
                'shopee_update_time',
                'shopee_pay_time',
                'shopee_ship_by_date',
                'shopee_return_request_due_date',
                'shopee_is_buyer_shop_collection',
                'shopee_goods_to_declare',
                'shopee_fulfillment_flag',
                'shopee_message_to_seller',
                'shopee_note',
                'shopee_note_update_time',
                'shopee_pending_terms',
                'shopee_recipient_address',
                'shopee_package_list',
                'shopee_invoice_data',
                'shopee_payment_info',
                'shopee_raw',
            ]);
        });

        Schema::table('transaksi_d', function (Blueprint $t) {
            $t->dropColumn([
                'shopee_item_id',
                'shopee_order_item_id',
                'shopee_model_id',
                'shopee_item_sku',
                'shopee_model_sku',
                'shopee_model_name',
                'shopee_model_original_price',
                'shopee_model_discounted_price',
                'shopee_weight',
                'shopee_add_on_deal',
                'shopee_add_on_deal_id',
                'shopee_main_item',
                'shopee_promotion_type',
                'shopee_promotion_id',
                'shopee_promotion_group_id',
                'shopee_image_url',
                'shopee_product_location_id',
                'shopee_item_raw',
            ]);
        });
    }
};
