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
        Schema::create('material_logs', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('material_id');
            $table->enum('type', ['IN', 'OUT']);
            $table->decimal('qty', 12, 2);
            $table->decimal('previous_qty', 12, 2)->default(0);
            $table->decimal('new_qty', 12, 2)->default(0);
            $table->string('reference_type')->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();

            $table->unsignedBigInteger('action_by');        
            $table->text('remarks')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('material_logs');
    }
};
