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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            
            // Basic Info
            $table->string('name');
            $table->string('model')->nullable(); // Stock Keeping Unit / Product Code
            $table->string('size')->nullable();
            $table->string('color', 100)->nullable();
            $table->string('hsn_code', 100)->nullable();
            $table->decimal('rrp', 20,2)->nullable();
            $table->string('product_type', 150)->nullable();
            // $table->unsignedInteger('dealer_id', 150)->nullable();
            $table->unsignedInteger('group_id');
            $table->string('image', 225)->nullable();
            $table->tinyInteger('status')->default(1);

            // Relations
            // $table->foreignId('dealer_id')->nullable()->constrained('users')->onDelete('set null');
            $table->foreign('group_id')->references('id')->on('groups')->onDelete('set null');

            $table->timestamps();
            $table->softDeletes(); // in case you need to archive products
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
