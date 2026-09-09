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
        Schema::create('product_returns', function (Blueprint $table) {
            $table->id();
            $table->string('return_number', 50)
                ->unique();

            $table->foreignId('order_id')
                ->constrained()
                ->restrictOnDelete();

            $table->foreignId('user_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            /*
             * requested
             * approved
             * rejected
             * received
             * refunded
             * closed
             */
            $table->string('status', 30)
                ->default('requested')
                ->index();

            $table->text('reason');

            $table->text('customer_note')
                ->nullable();

            $table->text('admin_note')
                ->nullable();

            $table->decimal('refund_amount', 12, 2)
                ->default(0);

            $table->timestamp('requested_at')
                ->nullable();

            $table->timestamp('approved_at')
                ->nullable();

            $table->timestamp('received_at')
                ->nullable();

            $table->timestamp('resolved_at')
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
        Schema::dropIfExists('product_returns');
    }
};
