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
        Schema::create('invoice_lines', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('invoice_id');           // FK → invoices.id
            $table->uuid('category_id')->nullable(); // FK → invoice_categories.id

            $table->string('description');
            $table->integer('quantity')->default(1);
            $table->decimal('unit_price', 15, 2)->default(0);
            // If your DB supports virtual columns:
            $table->decimal('line_total', 15, 2)
                  ->virtualAs('quantity * unit_price');

            $table->timestamps();

            // FKs
            $table->foreign('invoice_id')
                  ->references('id')->on('invoices')
                  ->onDelete('cascade');
            $table->foreign('category_id')
                  ->references('id')->on('invoice_categories')
                  ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoice_lines');
    }
};
