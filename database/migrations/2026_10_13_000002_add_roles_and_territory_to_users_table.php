<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('phone', 20)->nullable()->unique()->after('email');
            $table->enum('role', [
                'customer',
                'sub_agent',
                'agent',
                'distributor',
                'center',
                'driver',
                'admin',
            ])->default('customer')->after('password');
            $table->foreignId('territory_id')->nullable()->after('role')->constrained('territories')->nullOnDelete();
            $table->text('address')->nullable()->after('territory_id');
            $table->decimal('latitude', 10, 7)->nullable()->after('address');
            $table->decimal('longitude', 10, 7)->nullable()->after('latitude');
            $table->boolean('is_active')->default(true)->after('longitude');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['territory_id']);
            $table->dropColumn([
                'phone',
                'role',
                'territory_id',
                'address',
                'latitude',
                'longitude',
                'is_active',
            ]);
        });
    }
};
