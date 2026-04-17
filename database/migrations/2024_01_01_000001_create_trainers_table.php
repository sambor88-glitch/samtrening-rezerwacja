<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('trainers', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('name');
            $table->string('name_short')->nullable();
            $table->string('password_hash');
            $table->string('role')->default('trainer');
            $table->string('color')->nullable();
            $table->string('gradient')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trainers');
    }
};
