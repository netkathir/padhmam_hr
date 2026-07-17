<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shift_employee_types', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('shift_id')->constrained('shifts')->restrictOnDelete();
            $table->foreignId('employee_type_id')->constrained('employee_types')->restrictOnDelete();
            $table->timestamps();

            $table->unique(['shift_id', 'employee_type_id']);
            $table->index('shift_id');
            $table->index('employee_type_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shift_employee_types');
    }
};
