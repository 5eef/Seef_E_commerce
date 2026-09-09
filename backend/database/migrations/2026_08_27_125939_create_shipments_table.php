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
        Schema::create('shipments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('carrier')
                ->nullable();

            $table->string('tracking_number')
                ->nullable()
                ->index();

            /*
             * pending
             * ready
             * shipped
             * in_transit
             * delivered
             * failed
             * returned
             */
            $table->string('status', 30)
                ->default('pending')
                ->index();

            $table->decimal('shipping_cost', 12, 2)
                ->default(0);

            $table->timestamp('shipped_at')
                ->nullable();

            $table->timestamp('delivered_at')
                ->nullable();

            $table->json('metadata')
                ->nullable();
            $table->timestamps();
            $table->index([
                'order_id',
                'status',
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shipments');
    }
};
