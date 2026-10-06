<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('category_id')->constrained()->restrictOnDelete();
            $table->foreignId('brand_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('sku')->nullable()->unique();
            $table->string('status')->default('draft');
            $table->text('short_description')->nullable();
            $table->longText('description')->nullable();
            $table->unsignedInteger('price')->comment('BDT minor unit');
            $table->unsignedInteger('compare_at_price')->nullable()->comment('BDT minor unit');
            $table->unsignedInteger('cost_price')->nullable()->comment('BDT minor unit');
            $table->unsignedTinyInteger('discount_percent')->nullable();
            $table->timestamp('sale_starts_at')->nullable();
            $table->timestamp('sale_ends_at')->nullable();
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_refundable')->default(true);
            $table->unsignedSmallInteger('return_window_days')->default(7);
            $table->decimal('weight_kg', 8, 3)->nullable();
            $table->json('meta')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'published_at']);
            $table->index(['category_id', 'status']);
            $table->index(['brand_id', 'status']);
            $table->index(['is_featured', 'status']);
            $table->index(['sale_starts_at', 'sale_ends_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
