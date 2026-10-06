<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('popular_searches', function (Blueprint $table): void {
            $table->id();
            $table->string('label_en');
            $table->string('label_bn')->nullable();
            $table->string('keyword');
            $table->string('href');
            $table->unsignedInteger('sort_order')->default(0);
            $table->unsignedBigInteger('click_count')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['is_active', 'sort_order']);
            $table->index('keyword');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('popular_searches');
    }
};
