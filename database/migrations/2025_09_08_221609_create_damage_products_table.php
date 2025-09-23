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
        Schema::create('damage_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignId('product_variant_id')->nullable()->constrained('product_variants')->cascadeOnDelete();
            $table->unsignedBigInteger('shipment_product_id')->nullable();
            $table->foreign('shipment_product_id')
            ->references('id')
            ->on('shipment_products')
            ->onDelete('set null');
            $table->integer('quantity');
            $table->text('reason')->nullable();
            $table->enum('status', ['damage','return','repaired'])->default('damage');
            $table->timestamp('creationDate')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('damage_products');
    }
};
