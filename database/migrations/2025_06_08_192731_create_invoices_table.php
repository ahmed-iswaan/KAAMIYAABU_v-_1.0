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
        Schema::create('invoices', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('number')->unique();
            $table->uuid('property_id');          // FK → properties.id
            $table->uuid('directories_id');       // FK → directories.id

            $table->date('date');
            $table->date('due_date')->nullable();
            $table->decimal('total_amount', 15, 2)->default(0);

            // invoice features
            $table->string('invoice_type')->default('standard');
            $table->string('status')->default('draft');

            // fine configuration
            $table->decimal('fine_rate', 15, 2)->default(0);
            $table->string('fine_interval')->default('daily');
            $table->unsignedInteger('fine_grace_period')->nullable();

            // free-text messages
            $table->text('message_on_statement')->nullable();
            $table->text('message_to_customer')->nullable();

            $table->timestamps();

            // FKs
            $table->foreign('property_id')
                  ->references('id')->on('properties')
                  ->onDelete('cascade');
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
        Schema::dropIfExists('invoices');
    }
};
