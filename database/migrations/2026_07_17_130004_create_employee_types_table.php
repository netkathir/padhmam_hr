<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_types', function (Blueprint $table): void {
            $table->id();
            $table->string('code');
            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('is_system')->default(false);
            $table->boolean('requires_contractor')->default(false);
            $table->boolean('attendance_applicable')->default(true);
            $table->boolean('leave_applicable')->default(true);
            $table->boolean('payroll_applicable')->default(true);
            $table->boolean('overtime_applicable')->default(false);
            $table->string('default_shift_type')->default('fixed');
            $table->string('employee_number_prefix')->nullable();
            $table->unsignedInteger('display_order')->default(0);
            $table->string('status')->default('active');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            $table->unique('code');
            $table->index('name');
            $table->index('status');
            $table->index('is_system');
            $table->index('default_shift_type');
            $table->index('display_order');

            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('updated_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_types');
    }
};
