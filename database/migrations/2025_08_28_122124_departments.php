<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('departments', function (Blueprint $table) {
            $table->id(); // Primary Key
            $table->string('name'); 
            $table->smallInteger('status')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->smallInteger('sequence')->nullable();
            $table->timestamps(); 
            $table->softDeletes(); 
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('departments');
    }
};
