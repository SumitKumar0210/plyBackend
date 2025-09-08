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
        Schema::create('billings', function (Blueprint $table) {
            $table->id();
            
            // Basic Info
            $table->unsignedInteger('po_id')->nullable();
            $table->string('bill_no', 50)->nullable();
            $table->date('date')->nullable();
            $table->date('delivered_date')->nullable();
            $table->integer('consignee')->nullable();
            $table->string('invoice_no', 50)->nullable();
            $table->string('order_no', 50)->nullable();
            $table->string('dispatch_through', 20)->nullable();
            $table->string('dispatch_doc', 225)->nullable();
            $table->bigInteger('eway_bill')->nullable();
            $table->string('eway_date',50)->nullable();
            $table->string('vehicle_no', 25)->nullable();
            $table->decimal('total', 22,2)->nullable();
            $table->decimal('discount', 10,2)->nullable();
            $table->decimal('gst', 15,2)->nullable();
            $table->decimal('grand_total', 22,2)->nullable();
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
        Schema::dropIfExists('billings');
    }
};
