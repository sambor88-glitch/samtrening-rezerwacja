<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('chat_notifications', function (Blueprint $table) {
            $table->id();
            $table->string('trainer_id');
            $table->string('client_id');
            $table->integer('unread_trainer')->default(0);
            $table->integer('unread_client')->default(0);
            $table->timestamps();

            $table->unique(['trainer_id', 'client_id']);
            $table->foreign('trainer_id')->references('id')->on('trainers')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chat_notifications');
    }
};
