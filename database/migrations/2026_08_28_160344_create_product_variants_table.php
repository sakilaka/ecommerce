<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_variants', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('sku')->unique();
            $table->json('options')->nullable();
            $table->unsignedInteger('price')->nullable()->comment('BDT minor unit');
            $table->unsignedInteger('compare_at_price')->nullable()->comment('BDT minor unit');
            $table->integer('stock_quantity')->default(0);
            $table->integer('reserved_quantity')->default(0);
            $table->integer('low_stock_threshold')->default(5);
            $table->boolean('oversell_allowed')->default(true);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['product_id', 'is_active']);
            $table->index(['stock_quantity', 'reserved_quantity']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_variants');
    }
};
