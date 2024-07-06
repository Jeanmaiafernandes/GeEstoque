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
        Schema::table('products', function (Blueprint $table) {
            if (!Schema::hasColumn('products', 'product_name')) {
                $table->string('product_name');
            }
            if (!Schema::hasColumn('products', 'description')) {
                $table->string('description');
            }
            if (!Schema::hasColumn('products', 'price')) {
                $table->float('price');
            }
            if (!Schema::hasColumn('products', 'amount')) {
                $table->integer('amount');
            }
            if (!Schema::hasColumn('products', 'product_registration_date')) {
                $table->timestamp('product_registration_date')->nullable();
            }
            if (!Schema::hasColumn('products', 'product_withdrawal_date')) {
                $table->timestamp('product_withdrawal_date')->nullable();
            }
            if (!Schema::hasColumn('products', 'category_name')) {
                $table->unsignedBigInteger('category_name');
            }
            if (!Schema::hasColumn('products', 'supplier_id')) {
                $table->unsignedBigInteger('supplier_id');
            }

            if (!Schema::hasColumn('products', 'supplier_id')) {
                $table->foreign('supplier_id')->references('id')->on('suppliers');
            }
            if (!Schema::hasColumn('products', 'category_name')) {
                $table->foreign('category_name')->references('category_name')->on('category')->onDelete('cascade');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            //
        });
    }
};
