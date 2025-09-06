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
        Schema::create('product_variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->String('color')->nullable();
            $table->String('size')->nullable();
            $table->enum('clothes', ['sm', 'md','lg','xl','2xl','3xl','4xl','5xl','6xl','+xl'])->nullable();
            $table->string('sku')->nullable()->unique();
            $table->String('barcode')->nullable()->unique();
            $table->decimal('sellingPrice', 10, 2)->nullable();
            $table->json('images')->nullable();
            $table->timestamp('creationDate')->nullable();
            $table->text('notes')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_variants');
    }
};
