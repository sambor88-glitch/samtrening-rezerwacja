<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('client_prices', function (Blueprint $table) {
            $table->id();
            $table->string('trainer_id');
            $table->string('client_id');
            $table->decimal('price', 10, 2);
            $table->timestamps();

            $table->unique(['trainer_id', 'client_id']);
            $table->foreign('trainer_id')->references('id')->on('trainers')->onDelete('cascade');
            $table->foreign('client_id')->references('id')->on('clients')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('client_prices');
    }
};
