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
            Schema::create('invoice_schedules', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('property_id')->nullable()->constrained();
            $table->foreignUuid('directories_id')->nullable()->constrained('directories');
            $table->string('invoice_tag')->nullable();
            $table->string('ref_id')->nullable();
            $table->decimal('fine_rate', 15, 2)->default(0);
            $table->string('fine_interval')->default('daily');
            $table->unsignedInteger('fine_grace_period')->nullable();
            $table->string('due_days');

            $table->date('start_date');
            $table->date('next_invoice_date');
            $table->string('recurrence'); // daily, weekly, monthly
            $table->integer('total_cycles')->nullable(); // total number of invoices to generate
            $table->integer('generated_count')->default(0);
            $table->boolean('is_active')->default(true);

            $table->json('lines'); // store line items in JSON

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoice_schedules');
    }
};
