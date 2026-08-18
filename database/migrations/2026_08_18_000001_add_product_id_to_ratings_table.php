<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ratings', function (Blueprint $table) {
            $table->foreignId('product_id')
                ->nullable()
                ->after('seller_id')
                ->constrained('products')
                ->cascadeOnDelete();

            $table->unique(
                ['customer_id', 'product_id'],
                'ratings_customer_product_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::table('ratings', function (Blueprint $table) {
            $table->dropUnique('ratings_customer_product_unique');
            $table->dropConstrainedForeignId('product_id');
        });
    }
};
