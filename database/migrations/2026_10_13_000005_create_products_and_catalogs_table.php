<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('icon')->nullable();
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->string('sku')->unique();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('unit_name'); // ikat, 250g, 500g, kg, sak, pak, botol
            $table->decimal('base_price', 12, 2);
            $table->text('description')->nullable();
            $table->string('image')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('tier_prices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->enum('buyer_tier', ['sub_agent', 'agent', 'distributor']);
            $table->decimal('wholesale_price', 12, 2);
            $table->integer('moq')->default(1);
            $table->timestamps();

            $table->unique(['product_id', 'buyer_tier']);
        });

        Schema::create('merchant_inventories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('merchant_id')->constrained('merchants')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->integer('stock')->default(0);
            $table->decimal('custom_price', 12, 2)->nullable();
            $table->boolean('is_available')->default(true);
            $table->timestamps();

            $table->unique(['merchant_id', 'product_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('merchant_inventories');
        Schema::dropIfExists('tier_prices');
        Schema::dropIfExists('products');
        Schema::dropIfExists('categories');
    }
};
