<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(
            'product_option_value_product_variant',
            function (Blueprint $table): void {
                $table->unsignedBigInteger(
                    'product_option_value_id'
                );

                $table->unsignedBigInteger(
                    'product_variant_id'
                );

                $table->primary(
                    [
                        'product_option_value_id',
                        'product_variant_id',
                    ],
                    'pov_pv_primary'
                );

                $table->foreign(
                    'product_option_value_id',
                    'pov_pv_option_value_fk'
                )
                    ->references('id')
                    ->on('product_option_values')
                    ->cascadeOnDelete();

                $table->foreign(
                    'product_variant_id',
                    'pov_pv_variant_fk'
                )
                    ->references('id')
                    ->on('product_variants')
                    ->cascadeOnDelete();

                $table->index(
                    'product_variant_id',
                    'pov_pv_variant_idx'
                );
            }
        );
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'product_option_value_product_variant'
        );
    }
};
