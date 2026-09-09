<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_variants', function (Blueprint $table) {
            $table->id();

            $table->foreignId('product_id')
                ->constrained()
                ->cascadeOnDelete();

            /*
             * Nom lisible facultatif.
             *
             * Exemple :
             * Noir / XL
             */
            $table->string('name')
                ->nullable();

            /*
             * SKU unique de la variante.
             */
            $table->string('sku')
                ->unique();

            /*
             * Code-barres facultatif.
             */
            $table->string('barcode')
                ->nullable()
                ->unique();

            /*
             * Si NULL :
             * on utilise le prix du produit parent.
             */
            $table->decimal('price', 12, 2)
                ->nullable();

            $table->decimal('sale_price', 12, 2)
                ->nullable();

            /*
             * Coût interne.
             */
            $table->decimal('cost_price', 12, 2)
                ->nullable();

            /*
             * Une variante peut éventuellement
             * remplacer les dimensions du produit.
             */
            $table->unsignedInteger('weight_grams')
                ->nullable();

            $table->decimal('length_cm', 10, 2)
                ->nullable();

            $table->decimal('width_cm', 10, 2)
                ->nullable();

            $table->decimal('height_cm', 10, 2)
                ->nullable();

            $table->boolean('is_active')
                ->default(true)
                ->index();

            $table->unsignedInteger('sort_order')
                ->default(0);

            $table->timestamps();
            $table->softDeletes();

            $table->index([
                'product_id',
                'is_active',
            ]);

            $table->index([
                'product_id',
                'sort_order',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_variants');
    }
};
