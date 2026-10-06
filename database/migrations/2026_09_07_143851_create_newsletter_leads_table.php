<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('newsletter_leads', function (Blueprint $table): void {
            $table->id();
            $table->string('email')->nullable();
            $table->string('phone', 30)->nullable();
            $table->string('source')->default('homepage');
            $table->string('locale', 5)->default('en');
            $table->string('interest')->nullable();
            $table->timestamp('consented_at')->nullable();
            $table->timestamp('last_subscribed_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->text('admin_note')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index('email');
            $table->index('phone');
            $table->index(['is_active', 'created_at']);
            $table->index('source');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('newsletter_leads');
    }
};
