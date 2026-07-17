<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_emergency_contacts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();

            $table->string('name');
            $table->string('relationship');
            $table->string('primary_phone');
            $table->string('alternate_phone')->nullable();
            $table->text('address')->nullable();
            $table->boolean('is_primary')->default(false);

            $table->timestamps();

            $table->index('employee_id');
            $table->index('is_primary');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_emergency_contacts');
    }
};
