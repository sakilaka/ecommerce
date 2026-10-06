<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('coupon_id')->nullable()->constrained()->nullOnDelete();
            $table->string('order_number')->unique();
            $table->string('status')->default('pending');
            $table->string('payment_status')->default('unpaid');
            $table->string('payment_method')->default('cod');
            $table->string('customer_name');
            $table->string('customer_email')->nullable();
            $table->string('customer_phone');
            $table->json('shipping_address');
            $table->string('shipping_district');
            $table->string('shipping_area')->nullable();
            $table->unsignedInteger('subtotal')->default(0)->comment('BDT minor unit');
            $table->unsignedInteger('discount_total')->default(0)->comment('BDT minor unit');
            $table->unsignedInteger('shipping_total')->default(0)->comment('BDT minor unit');
            $table->unsignedInteger('tax_total')->default(0)->comment('BDT minor unit');
            $table->unsignedInteger('grand_total')->default(0)->comment('BDT minor unit');
            $table->text('customer_note')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('shipped_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
            $table->index(['payment_status', 'payment_method']);
            $table->index(['customer_phone', 'created_at']);
            $table->index(['shipping_district', 'shipping_area']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
