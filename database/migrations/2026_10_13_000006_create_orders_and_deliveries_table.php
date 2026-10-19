<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number')->unique();
            $table->foreignId('customer_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('merchant_id')->constrained('merchants')->cascadeOnDelete();
            $table->enum('order_type', ['retail', 'procurement'])->default('retail');
            $table->enum('status', [
                'pending',
                'paid',
                'processing',
                'ready_to_ship',
                'on_delivery',
                'delivered',
                'completed',
                'cancelled',
            ])->default('pending');
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('shipping_fee', 12, 2)->default(0);
            $table->decimal('platform_fee', 12, 2)->default(0);
            $table->decimal('total_amount', 12, 2)->default(0);
            $table->string('payment_method')->nullable(); // qris, va_bri, va_bni, va_mandiri, cod
            $table->enum('payment_status', ['unpaid', 'paid', 'expired', 'refunded'])->default('unpaid');
            $table->timestamp('paid_at')->nullable();
            $table->text('notes')->nullable();
            $table->text('shipping_address')->nullable();
            $table->decimal('shipping_latitude', 10, 7)->nullable();
            $table->decimal('shipping_longitude', 10, 7)->nullable();
            $table->timestamps();

            $table->index(['merchant_id', 'status']);
            $table->index(['customer_id', 'status']);
        });

        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->string('product_name');
            $table->string('unit_name');
            $table->integer('quantity');
            $table->decimal('price', 12, 2);
            $table->decimal('subtotal', 12, 2);
            $table->timestamps();
        });

        Schema::create('deliveries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->unique()->constrained('orders')->cascadeOnDelete();
            $table->enum('delivery_type', ['ojol', 'merchant_courier', 'self_pickup'])->default('ojol');
            $table->decimal('distance_km', 6, 2)->default(0);
            $table->decimal('fee', 12, 2)->default(0);
            $table->foreignId('driver_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('pod_image_path')->nullable();
            $table->string('completion_otp', 6)->nullable();
            $table->enum('status', [
                'pending',
                'assigned',
                'picked_up',
                'delivered',
                'cancelled'
            ])->default('pending');
            $table->timestamp('assigned_at')->nullable();
            $table->timestamp('picked_up_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamps();
        });

        Schema::create('escrow_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->unique()->constrained('orders')->cascadeOnDelete();
            $table->decimal('gross_amount', 12, 2);
            $table->decimal('merchant_portion', 12, 2);
            $table->decimal('driver_portion', 12, 2)->default(0);
            $table->decimal('platform_portion', 12, 2)->default(0);
            $table->enum('status', ['held', 'released', 'refunded'])->default('held');
            $table->timestamp('released_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('escrow_transactions');
        Schema::dropIfExists('deliveries');
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');
    }
};
