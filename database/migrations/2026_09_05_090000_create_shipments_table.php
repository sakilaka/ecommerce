<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shipments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->string('status')->default('pending');
            $table->string('courier_name')->nullable();
            $table->string('tracking_number')->nullable();
            $table->string('tracking_url')->nullable();
            $table->unsignedInteger('delivery_charge')->default(0)->comment('BDT minor unit');
            $table->unsignedInteger('cod_charge')->default(0)->comment('BDT minor unit');
            $table->unsignedInteger('cod_amount')->default(0)->comment('BDT minor unit');
            $table->string('recipient_name');
            $table->string('recipient_phone');
            $table->string('recipient_district');
            $table->string('recipient_area')->nullable();
            $table->text('recipient_address');
            $table->timestamp('estimated_delivery_at')->nullable();
            $table->timestamp('shipped_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->text('admin_note')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['order_id', 'created_at']);
            $table->index(['status', 'created_at']);
            $table->index(['recipient_phone', 'created_at']);
            $table->index(['recipient_district', 'recipient_area']);
            $table->unique(['courier_name', 'tracking_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shipments');
    }
};
