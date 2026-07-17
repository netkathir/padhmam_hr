<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_addresses', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();

            $table->string('address_type');
            $table->string('address_line_1');
            $table->string('address_line_2')->nullable();
            $table->string('city');
            $table->string('district')->nullable();
            $table->string('state');
            $table->string('country')->default('India');
            $table->string('postal_code');
            $table->boolean('is_same_as_current')->default(false);

            $table->timestamps();

            $table->unique(['employee_id', 'address_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_addresses');
    }
};
