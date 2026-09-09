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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number', 50)
                ->unique();

            /*
             * Nullable pour supporter checkout invité.
             */
            $table->foreignId('user_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->foreignId('coupon_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            /*
             * Snapshots client.
             */
            $table->string('customer_name');
            $table->string('email');
            $table->string('phone', 30);

            /*
             * pending
             * confirmed
             * processing
             * shipped
             * delivered
             * cancelled
             * completed
             */
            $table->string('status', 30)
                ->default('pending')
                ->index();

            /*
             * pending
             * paid
             * failed
             * refunded
             * partially_refunded
             */
            $table->string('payment_status', 30)
                ->default('pending')
                ->index();

            $table->char('currency', 3)
                ->default('MAD');

            $table->decimal('subtotal', 12, 2);
            $table->decimal('discount_total', 12, 2)
                ->default(0);

            $table->decimal('shipping_total', 12, 2)
                ->default(0);

            $table->decimal('tax_total', 12, 2)
                ->default(0);

            $table->decimal('grand_total', 12, 2);

            /*
             * Snapshot du coupon.
             */
            $table->string('coupon_code', 100)
                ->nullable();

            /*
             * Ne jamais dépendre d'une Address modifiable
             * après la commande.
             */
            $table->json('shipping_address');

            $table->json('billing_address')
                ->nullable();

            $table->text('customer_note')
                ->nullable();

            $table->text('admin_note')
                ->nullable();

            $table->timestamp('placed_at')
                ->nullable()
                ->index();

            $table->timestamp('confirmed_at')
                ->nullable();

            $table->timestamp('cancelled_at')
                ->nullable();

            $table->timestamp('completed_at')
                ->nullable();

            $table->timestamps();

            $table->index([
                'user_id',
                'created_at',
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
