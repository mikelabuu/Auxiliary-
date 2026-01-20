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
        Schema::create('staff', function (Blueprint $table) {
            $table->id(); // id INT AUTO_INCREMENT PRIMARY KEY
            $table->string('name'); // name VARCHAR(255)
            $table->string('email')->unique(); // email VARCHAR(255) - should be unique
            $table->string('password'); // password VARCHAR(255)
            $table->rememberToken(); // adds remember_token VARCHAR(100) NULL
            $table->timestamps(); // created_at + updated_at (TIMESTAMP)
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('staff');
    }
};
