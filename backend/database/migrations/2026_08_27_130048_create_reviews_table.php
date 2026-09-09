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
        Schema::create('reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('product_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('order_item_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->unsignedTinyInteger('rating');

            $table->string('title')
                ->nullable();

            $table->text('body')
                ->nullable();

            /*
             * pending
             * approved
             * rejected
             */
            $table->string('status', 30)
                ->default('pending')
                ->index();

            $table->boolean('is_verified_purchase')
                ->default(false)
                ->index();

            $table->timestamp('approved_at')
                ->nullable();

            $table->timestamps();
            $table->unique([
                'user_id',
                'product_id',
            ]);
            $table->index([
                'product_id',
                'status',
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reviews');
    }
};
