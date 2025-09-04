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
        Schema::create('shipments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_id')->constrained('suppliers')->cascadeOnDelete();
            $table->string('importer')->nullable();
            $table->foreignId('admin_id')->nullable()->constrained('admins')->nullOnDelete();
            $table->unsignedBigInteger('shipmentProductsCount')->default(0);
            $table->decimal('totalPrice', 15, 2)->default(0);
             $table->decimal('discount', 10, 2)->nullable();
            $table->decimal('extraAmount', 10, 2)->nullable();
            $table->enum('discountType', ['percentage', 'pounds'])->nullable();
            $table->enum('taxType', ['percentage', 'pounds'])->nullable();
            $table->decimal('invoiceAfterDiscount', 15, 2)->nullable();
            $table->decimal('paidAmount', 10, 2);
            $table->decimal('remainingAmount', 10, 2)->nullable();
            $table->enum('status', ['indebted', 'completed','partialReturn','return'])->default('indebted');
            $table->text('returnReason')->nullable();
            $table->enum('payment', ['visa','cash','wallet','instapay'])->nullable();
            $table->timestamp('creationDate')->nullable();
            $table->text('description')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shipments');
    }
};
