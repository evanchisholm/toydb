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
        Schema::table('toys', function (Blueprint $table) {
            $table->integer('ebay_listings_count')->nullable()->after('estimated_value');
            $table->decimal('ebay_average_price', 10, 2)->nullable()->after('ebay_listings_count');
            $table->timestamp('ebay_last_searched_at')->nullable()->after('ebay_average_price');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('toys', function (Blueprint $table) {
            $table->dropColumn(['ebay_listings_count', 'ebay_average_price', 'ebay_last_searched_at']);
        });
    }
};
