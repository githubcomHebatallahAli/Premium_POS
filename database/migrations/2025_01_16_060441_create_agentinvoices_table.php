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
        Schema::create('agentinvoices', function (Blueprint $table) {
            $table->id();
            $table->string('responsibleName');
            $table->string('distributorName');
            $table->timestamp('creationDate')->nullable();
            $table->unsignedBigInteger('invoiceProductCount')->default(0);
            $table->decimal('totalInvoicePrice', 15, 2)->default(0);
            $table->enum('status', ['delivery', 'distribution'])->default('distribution')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('agentinvoices');
    }
};
