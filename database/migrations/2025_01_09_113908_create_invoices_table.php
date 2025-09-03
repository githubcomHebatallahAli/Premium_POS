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
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->string('customerName');
            $table->string('customerPhone');
            $table->foreignId('admin_id')->nullable()->constrained('admins')->nullOnDelete();
            $table->timestamp('creationDate')->nullable();
            $table->unsignedBigInteger('invoiceProductCount')->default(0);
            $table->decimal('totalInvoicePrice', 15, 2)->default(0);
            $table->decimal('discount', 10, 2)->nullable();
            $table->decimal('extraAmount', 10, 2)->nullable();
            $table->enum('discountType', ['percentage', 'pounds'])->default('pounds');
            $table->enum('taxType', ['percentage', 'pounds'])->default('percentage');
            $table->decimal('invoiceAfterDiscount', 15, 2)->nullable();
            $table->decimal('profit', 10, 2)->nullable();
            $table->decimal('paidAmount', 10, 2);
            $table->decimal('remainingAmount', 10, 2)->nullable();
            $table->enum('pullType', ['fifo', 'manual'])->default('fifo');
            $table->enum('status', ['completed','return','partialReturn','indebted'])->nullable();
            $table->enum('payment', ['visa','cash','wallet','instapay'])->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
