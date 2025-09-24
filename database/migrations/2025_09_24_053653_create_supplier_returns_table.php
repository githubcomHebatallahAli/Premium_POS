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
        Schema::create('supplier_returns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('damage_product_id')->constrained('damage_products')->cascadeOnDelete();
            $table->integer('returned_quantity');
            $table->decimal('refund_amount', 15, 2)->default(0); 
            $table->decimal('loss_amount', 15, 2)->default(0);  
            $table->timestamp('creationDate')->nullable();
            $table->text('note')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('supplier_returns');
    }
};
