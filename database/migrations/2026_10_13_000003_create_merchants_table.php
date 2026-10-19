<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('merchants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('territory_id')->constrained('territories')->cascadeOnDelete();
            $table->enum('tier', ['sub_agent', 'agent', 'distributor', 'center']);
            $table->string('store_name');
            $table->string('slug')->unique();
            $table->string('phone', 20)->nullable();
            $table->text('address')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->enum('status', ['active', 'inactive', 'suspended'])->default('active');
            $table->boolean('is_open')->default(true);
            $table->decimal('min_order_amount', 12, 2)->default(0);
            $table->boolean('self_delivery_active')->default(true);
            $table->decimal('delivery_radius_km', 6, 2)->default(10.00);
            $table->time('opening_time')->nullable();
            $table->time('closing_time')->nullable();
            $table->timestamps();

            $table->index(['tier', 'status', 'is_open']);
            $table->index(['territory_id', 'tier', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('merchants');
    }
};
