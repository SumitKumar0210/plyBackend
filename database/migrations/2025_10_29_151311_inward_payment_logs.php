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
        Schema::create('inward_payment_logs', function (Blueprint $table) {
            $table->bigIncrements('id'); // bigint primary key
            $table->smallInteger('inward_id')->nullable();
            $table->string('payment_mode', 50)->nullable();
            $table->string('reference_no ', 225)->nullable();
            $table->decimal('paid_amount', 20, 2)->nullable();
            $table->decimal('due', 20, 2)->nullable();
            $table->string('date', 20)->nullable();
            $table->smallInteger('added_by')->nullable();
            $table->timestamps(); 
            $table->softDeletes(); 
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('inward_payment_logs');
    }
};
