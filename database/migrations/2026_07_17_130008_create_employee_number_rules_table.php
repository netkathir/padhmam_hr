<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_number_rules', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('branch_id')->constrained('branches')->restrictOnDelete();
            $table->foreignId('employee_type_id')->constrained('employee_types')->restrictOnDelete();

            $table->string('rule_name');
            $table->string('prefix')->nullable();
            $table->boolean('include_branch_code')->default(false);
            $table->boolean('include_employee_type_prefix')->default(false);
            $table->string('employee_type_prefix')->nullable();
            $table->boolean('include_year')->default(false);
            $table->string('year_format')->nullable();
            $table->string('separator')->nullable();
            $table->unsignedTinyInteger('serial_number_length');
            $table->unsignedBigInteger('starting_number');
            $table->string('reset_frequency')->default('never');
            $table->date('effective_from');
            $table->date('effective_to')->nullable();
            $table->boolean('is_default')->default(true);
            $table->string('status')->default('draft');
            $table->text('description')->nullable();

            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            $table->index('branch_id');
            $table->index('employee_type_id');
            $table->index('status');
            $table->index('effective_from');
            $table->index('effective_to');
            $table->index('is_default');
            $table->index('reset_frequency');
            $table->index(['branch_id', 'employee_type_id', 'status']);

            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('updated_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_number_rules');
    }
};
