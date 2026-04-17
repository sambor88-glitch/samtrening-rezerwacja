<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('blocked_slots', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('trainer_id');
            $table->date('date');
            $table->string('time')->nullable();
            $table->boolean('full_day')->default(false);
            $table->text('reason')->nullable();
            $table->timestamps();

            $table->foreign('trainer_id')->references('id')->on('trainers')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('blocked_slots');
    }
};
