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
        Schema::create('production_quality_checks', function (Blueprint $table) {
            $table->id();
            
            // Basic Info
            $table->unsignedInteger('po_id')->nullable();
            $table->string('image', 225)->nullable();
            $table->longText('comment', 225)->nullable();
            $table->tinyInteger('status')->default(0);

           
            // Relations
            $table->foreign('po_id')->references('id')->on('production_orders')->onDelete('set null');

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
        Schema::dropIfExists('production_quality_checks');
    }
};
