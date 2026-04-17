<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('availability', function (Blueprint $table) {
            $table->id();
            $table->string('trainer_id');
            $table->string('day_of_week'); // mon, tue, wed, thu, fri, sat, sun
            $table->string('start_time');
            $table->string('end_time');
            $table->boolean('active')->default(true);
            $table->timestamps();

            $table->foreign('trainer_id')->references('id')->on('trainers')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('availability');
    }
};
