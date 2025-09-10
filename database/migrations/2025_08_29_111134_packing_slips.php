<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('packing_slips', function (Blueprint $table) {
            $table->id();
            
            // Basic Info
            $table->unsignedInteger('po_id');
            $table->unsignedInteger('product_id');
            $table->string('store', 150)->nullable();
            $table->string('material_type', 80)->nullable();
            $table->integer('no_of_cartoon')->nullable();
            $table->text('description')->nullable();
            $table->tinyInteger('status')->default(0);

            // Relations
            $table->foreign('po_id')->references('id')->on('production_orders')->onDelete('set null');
            $table->foreign('product_id')->references('id')->on('products')->onDelete('set null');

            $table->timestamps();
            $table->softDeletes(); // in case you need to archive products
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('packing_slips');
    }
};
