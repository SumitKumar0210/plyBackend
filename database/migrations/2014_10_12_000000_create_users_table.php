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
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->rememberToken();
            $table->foreignId('current_team_id')->nullable();
            $table->string('profile_photo_path', 2048)->nullable();


            $table->bigInteger('mobile')->nullable();
            $table->string('city')->nullable();
            $table->text('address')->nullable();
            $table->integer('state_id')->nullable();
            $table->string('image',225)->nullable();
            $table->integer('user_type_id')->nullable();
            $table->text('access_token')->nullable();
            $table->string('token_expires_at',20)->nullable();
            $table->text('reset_token')->nullable();
            $table->string('reset_link_expires_at',20)->nullable();
            $table->integer('created_by')->nullable();
            $table->tinyInteger('status')->default(1);
            $table->softDeletes(); 

            $table->timestamps();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
