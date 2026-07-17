<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_contacts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('employee_id')->unique()->constrained('employees')->cascadeOnDelete();

            $table->string('personal_mobile');
            $table->string('alternate_mobile')->nullable();
            $table->string('personal_email')->nullable();
            $table->string('official_email')->nullable()->unique();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_contacts');
    }
};
