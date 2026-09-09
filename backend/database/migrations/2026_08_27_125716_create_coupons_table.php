<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('coupons', function (Blueprint $table) {
            $table->id();
            $table->string('code', 100)
                ->unique();

            $table->string('name')
                ->nullable();

            $table->text('description')
                ->nullable();

            /*
             * fixed
             * percentage
             */
            $table->string('type', 30)
                ->index();

            $table->decimal('value', 12, 2);

            $table->decimal('minimum_order_amount', 12, 2)
                ->nullable();

            $table->decimal('maximum_discount_amount', 12, 2)
                ->nullable();

            $table->unsignedInteger('usage_limit')
                ->nullable();

            $table->unsignedInteger('usage_limit_per_user')
                ->nullable();

            $table->timestamp('starts_at')
                ->nullable()
                ->index();

            $table->timestamp('ends_at')
                ->nullable()
                ->index();

            $table->boolean('is_active')
                ->default(true)
                ->index();

            $table->json('metadata')
                ->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('coupons');
    }
};
