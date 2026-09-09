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
        Schema::create('return_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_return_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('order_item_id')
                ->constrained()
                ->restrictOnDelete();

            $table->unsignedInteger('quantity');

            $table->string('reason')
                ->nullable();

            $table->string('condition', 50)
                ->nullable();

            /*
             * refund
             * replacement
             * store_credit
             */
            $table->string('resolution', 30)
                ->nullable();

            $table->decimal('refund_amount', 12, 2)
                ->default(0);

            $table->boolean('restock')
                ->default(false);
            $table->timestamps();
            $table->unique([
                'product_return_id',
                'order_item_id',
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('return_items');
    }
};
