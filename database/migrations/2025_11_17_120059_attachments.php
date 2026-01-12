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
         Schema::create('attachments', function (Blueprint $table) {
            $table->id();
            $table->string('doc');              
            $table->unsignedBigInteger('pp_id');  
            $table->string('department');       
            $table->unsignedBigInteger('action_by'); 
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
                Schema::dropIfExists('attachments');
    }
};
