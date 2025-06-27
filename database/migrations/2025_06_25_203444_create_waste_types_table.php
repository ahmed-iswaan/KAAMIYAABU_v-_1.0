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
            $table->string('unit')->nullable();
            $table->decimal('default_amount', 10, 2)->nullable();
            $table->unsignedInteger('index')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('waste_types');
    }
};
