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
        Schema::create('depts', function (Blueprint $table) {
            $table->id();
            $table->string('customerName');
            $table->string('sellerName');
            $table->timestamp('creationDate')->nullable();
            $table->unsignedBigInteger('deptProductCount')->default(0);
            $table->decimal('totalDepetPrice', 15, 2)->default(0);
            $table->decimal('discount', 10, 2)->nullable();
            $table->decimal('extraAmount', 10, 2)->nullable();
            $table->decimal('paidAmount', 10, 2);
            $table->decimal('remainingAmount', 10, 2)->nullable();
            $table->decimal('depetAfterDiscount', 15, 2)->nullable();
            $table->decimal('profit', 10, 2)->nullable();
            $table->enum('status', ['pending', 'paid'])->default('pending');
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('depts');
    }
};
