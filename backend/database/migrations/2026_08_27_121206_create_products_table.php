<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();

            /*
            |--------------------------------------------------------------------------
            | Identification
            |--------------------------------------------------------------------------
            */

            $table->string('name');
            $table->string('slug')->unique();

            /*
            |--------------------------------------------------------------------------
            | Description
            |--------------------------------------------------------------------------
            */

            $table->text('short_description')->nullable();
            $table->longText('description')->nullable();

            /*
            |--------------------------------------------------------------------------
            | Référence produit
            |--------------------------------------------------------------------------
            |
            | Nullable car un produit avec variantes peut avoir ses SKU
            | principalement dans product_variants.
            |
            */

            $table->string('sku')
                ->nullable()
                ->unique();

            /*
            |--------------------------------------------------------------------------
            | Prix
            |--------------------------------------------------------------------------
            */

            $table->decimal('base_price', 12, 2);

            $table->decimal('sale_price', 12, 2)
                ->nullable();

            // Coût interne, jamais destiné au catalogue public.
            $table->decimal('cost_price', 12, 2)
                ->nullable();

            /*
            |--------------------------------------------------------------------------
            | Publication
            |--------------------------------------------------------------------------
            */

            $table->string('status', 30)
                ->default('draft')
                ->index();

            $table->boolean('is_featured')
                ->default(false)
                ->index();

            $table->timestamp('published_at')
                ->nullable()
                ->index();

            /*
            |--------------------------------------------------------------------------
            | Poids / dimensions
            |--------------------------------------------------------------------------
            |
            | On indique volontairement les unités dans les noms.
            |
            */

            $table->unsignedInteger('weight_grams')
                ->nullable();

            $table->decimal('length_cm', 10, 2)
                ->nullable();

            $table->decimal('width_cm', 10, 2)
                ->nullable();

            $table->decimal('height_cm', 10, 2)
                ->nullable();

            /*
            |--------------------------------------------------------------------------
            | SEO
            |--------------------------------------------------------------------------
            */

            $table->string('seo_title')->nullable();

            $table->text('seo_description')->nullable();

            /*
            |--------------------------------------------------------------------------
            | Dates
            |--------------------------------------------------------------------------
            */

            $table->timestamps();

            // Suppression logique.
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
