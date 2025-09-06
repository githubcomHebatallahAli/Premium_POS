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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained('categories')->cascadeOnDelete();
            $table->foreignId('brand_id')->constrained('brands')->cascadeOnDelete();
            $table->string('name');
            $table->decimal('sellingPrice');
            $table->String('mainImage')->nullable();
            // $table->String('color')->nullable();
            // $table->String('size')->nullable();
            // $table->enum('clothes', ['sm', 'md','lg','xl','2xl','3xl','4xl','5xl','6xl','+xl'])->nullable();
            $table->String('country')->nullable();
            $table->string('sku')->nullable()->unique();
            $table->String('barcode')->nullable()->unique();
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
        Schema::dropIfExists('products');
    }
};
