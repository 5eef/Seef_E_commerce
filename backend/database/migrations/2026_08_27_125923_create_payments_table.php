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
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')
                ->constrained()
                ->cascadeOnDelete();

            /*
             * cod
             * card
             * bank_transfer
             */
            $table->string('method', 40)
                ->index();

            $table->string('provider', 100)
                ->nullable();

            $table->string('provider_reference')
                ->nullable()
                ->unique();

            /*
             * pending
             * authorized
             * paid
             * failed
             * cancelled
             * refunded
             * partially_refunded
             */
            $table->string('status', 30)
                ->default('pending')
                ->index();

            $table->decimal('amount', 12, 2);

            $table->char('currency', 3)
                ->default('MAD');

            $table->timestamp('paid_at')
                ->nullable();

            $table->timestamp('failed_at')
                ->nullable();

            $table->timestamp('refunded_at')
                ->nullable();

            $table->text('failure_reason')
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
        Schema::dropIfExists('payments');
    }
};
