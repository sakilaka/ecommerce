<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shipments', function (Blueprint $table): void {
            $table->foreignId('return_request_id')->nullable()->after('order_id')->constrained()->cascadeOnDelete();
            $table->string('direction')->default('outbound')->after('return_request_id');

            $table->index(['direction', 'status']);
            $table->index(['return_request_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::table('shipments', function (Blueprint $table): void {
            $table->dropIndex(['direction', 'status']);
            $table->dropIndex(['return_request_id', 'created_at']);
            $table->dropConstrainedForeignId('return_request_id');
            $table->dropColumn('direction');
        });
    }
};
