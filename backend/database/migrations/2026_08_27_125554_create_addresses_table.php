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
        Schema::create('addresses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('label', 50)
                ->nullable();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('phone', 30);
            $table->string('address_line_1');
            $table->string('address_line_2')->nullable();
            $table->string('city', 120);
            $table->string('region', 120)->nullable();
            $table->string('postal_code', 30)->nullable();
            $table->char('country_code', 2)->default('MA');
            $table->text('delivery_instructions')->nullable();
            $table->boolean('is_default_shipping')->default(false);
            $table->boolean('is_default_billing')->default(false);
            $table->timestamps();
            $table->index(['user_id', 'is_default_shipping']);
            $table->index(['user_id', 'is_default_billing']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('addresses');
    }
};
