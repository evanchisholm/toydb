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
        Schema::create('toys', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('brand')->nullable();
            $table->string('category')->nullable();
            $table->text('description')->nullable();
            $table->string('condition')->default('Good'); // Mint, Excellent, Good, Fair, Poor
            $table->decimal('purchase_price', 10, 2)->nullable();
            $table->decimal('estimated_value', 10, 2)->nullable();
            $table->date('purchase_date')->nullable();
            $table->date('manufacture_date')->nullable();
            $table->string('serial_number')->nullable();
            $table->boolean('in_box')->default(false);
            $table->text('notes')->nullable();
            $table->string('image_path')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('toys');
    }
};

