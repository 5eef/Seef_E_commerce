<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventories', function (Blueprint $table) {
            $table->id();

            /*
             * Une variante possède exactement
             * un enregistrement d'inventaire.
             */
            $table->foreignId('product_variant_id')
                ->unique()
                ->constrained()
                ->restrictOnDelete();

            /*
             * Stock réellement disponible.
             */
            $table->unsignedInteger('on_hand_quantity')
                ->default(0);

            /*
             * Stock réservé par des commandes
             * encore non finalisées.
             */
            $table->unsignedInteger('reserved_quantity')
                ->default(0);

            /*
             * Seuil d'alerte admin.
             */
            $table->unsignedInteger('low_stock_threshold')
                ->default(5);

            $table->timestamps();

            $table->index('on_hand_quantity');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventories');
    }
};
