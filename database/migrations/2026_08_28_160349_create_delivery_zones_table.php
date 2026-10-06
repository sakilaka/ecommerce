<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('delivery_zones', function (Blueprint $table): void {
            $table->id();
            $table->string('district');
            $table->string('area')->nullable();
            $table->unsignedInteger('delivery_charge')->comment('BDT minor unit');
            $table->unsignedInteger('cod_charge')->default(0)->comment('BDT minor unit');
            $table->unsignedSmallInteger('estimated_min_days')->default(1);
            $table->unsignedSmallInteger('estimated_max_days')->default(5);
            $table->boolean('cod_available')->default(true);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['district', 'area']);
            $table->index(['is_active', 'district']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('delivery_zones');
    }
};
