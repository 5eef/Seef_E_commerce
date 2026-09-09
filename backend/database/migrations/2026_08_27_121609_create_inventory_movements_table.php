<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_movements', function (Blueprint $table) {
            $table->id();

            $table->foreignId('inventory_id')
                ->constrained()
                ->restrictOnDelete();

            /*
             * Exemples :
             *
             * stock_in
             * sale
             * reservation
             * release
             * return
             * adjustment
             */
            $table->string('type', 40)
                ->index();

            $table->string('balance', 20)
                ->index();

            /*
             * Peut être positif ou négatif.
             *
             * +10 entrée stock
             * -2 vente
             */
            $table->integer('quantity');

            /*
             * Snapshot pour audit.
             */
            $table->unsignedInteger('quantity_before');

            $table->unsignedInteger('quantity_after');

            /*
             * Référence polymorphique facultative.
             *
             * Exemple :
             * Order #100
             * ProductReturn #23
             */
            $table->nullableMorphs('reference');

            $table->string('reason')
                ->nullable();

            $table->json('metadata')
                ->nullable();

            /*
             * Admin/utilisateur ayant déclenché
             * le mouvement, si applicable.
             */
            $table->foreignId('user_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->timestamps();

            $table->index([
                'inventory_id',
                'created_at',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_movements');
    }
};
