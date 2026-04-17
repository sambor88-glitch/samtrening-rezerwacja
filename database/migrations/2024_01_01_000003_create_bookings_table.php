<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('bookings', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('trainer_id');
            $table->string('client_id')->nullable();
            $table->string('client_name')->nullable();
            $table->date('date');
            $table->string('time');
            $table->integer('duration')->default(60);
            $table->string('type')->default('individual');
            $table->string('status')->default('confirmed');
            $table->boolean('completed')->default(false);
            $table->boolean('paid')->default(false);
            $table->string('package_id')->nullable();
            $table->decimal('price', 10, 2)->nullable();
            $table->text('note')->nullable();
            $table->timestamps();

            $table->foreign('trainer_id')->references('id')->on('trainers')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
