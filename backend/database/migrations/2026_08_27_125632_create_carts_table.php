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
        Schema::create('carts', function (Blueprint $table) {
            $table->id();
            /*
            *nillable afin de supporter également les paniers invité
            */
            $table->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete();

            /*
            Token opaque pour invité , il sera manipulé plus tard via un cookie laravel chifréé
            */

            $table->uuid('guest_token')->nullable()->unique();

            /*
            actove , convertier ,abandoner , éxpirer
            */

            $table->string('status', 30)->default('active')->index();
            $table->timestamp('expires_at')->nullable()->index();

            $table->timestamps();
            $table->index(['user_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('carts');
    }
};
