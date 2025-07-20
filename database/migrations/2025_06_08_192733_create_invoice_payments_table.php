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
        Schema::create('invoice_payments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('payment_id');           // FK → payments.id
            $table->uuid('invoice_id');           // FK → invoices.id
            $table->decimal('applied_amount', 15, 2);
            $table->string('status')->default('active');
            $table->timestamps();

            // FKs
            $table->foreign('payment_id')
                  ->references('id')->on('payments')
                  ->onDelete('cascade');
            $table->foreign('invoice_id')
                  ->references('id')->on('invoices')
                  ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoice_payments');
    }
};
