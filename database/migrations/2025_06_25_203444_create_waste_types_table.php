<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('waste_types', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('unit'); // e.g., 'kg', 'ltr'
            $table->decimal('unit_quantity', 8, 2)->default(1); // value like 10kg = 10
            $table->integer('index')->nullable();
            $table->decimal('total_collection', 10, 2)->default(0); // auto-updated
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('waste_types');
    }
};
