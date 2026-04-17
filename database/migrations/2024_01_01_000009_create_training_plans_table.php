<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('training_plans', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('trainer_id');
            $table->string('client_id');
            $table->string('name');
            $table->jsonb('exercises')->default('[]');
            $table->date('assigned_date')->nullable();
            $table->string('status')->default('active');
            $table->timestamps();

            $table->foreign('trainer_id')->references('id')->on('trainers')->onDelete('cascade');
            $table->foreign('client_id')->references('id')->on('clients')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('training_plans');
    }
};
