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
        Schema::create('payments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('number')->unique();
            $table->uuid('directories_id');

            $table->date('date');
            $table->decimal('amount', 15, 2);

            // ✅ Add these directly in the correct order
            $table->decimal('credit_used', 15, 2)->default(0);
            $table->decimal('overpaid_amount', 15, 2)->default(0);
            $table->decimal('total_applied_to_invoices', 15, 2)->default(0);

            $table->string('method')->nullable();
            $table->string('bank')->nullable();
            $table->string('ref')->nullable();
            $table->string('status')->default('Pending');
            $table->string('payment_slip')->nullable();
            $table->text('note')->nullable();
            $table->text('cancel_note')->nullable();
            $table->timestamps();

            // FKs
            $table->foreign('directories_id')
                  ->references('id')->on('directories')
                  ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
