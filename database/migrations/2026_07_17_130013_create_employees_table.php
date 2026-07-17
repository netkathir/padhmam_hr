<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employees', function (Blueprint $table): void {
            $table->id();
            $table->uuid('employee_uuid')->unique();

            $table->foreignId('branch_id')->constrained('branches')->restrictOnDelete();
            $table->foreignId('employee_type_id')->constrained('employee_types')->restrictOnDelete();
            $table->string('employee_number')->nullable()->unique();

            $table->string('first_name');
            $table->string('middle_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('display_name');

            // Nullable at the database level to support minimal Draft
            // registration (spec section 36: a Draft requires only Employee
            // Type, First Name, and Date of Birth or Date of Joining).
            // CompleteEmployeeRegistrationRequest enforces these as
            // mandatory before an Employee can become Active.
            $table->date('date_of_birth')->nullable();
            $table->string('gender')->nullable();
            $table->string('marital_status')->nullable();
            $table->string('blood_group')->nullable();
            $table->string('nationality')->default('India');
            $table->string('photo_path')->nullable();

            $table->date('date_of_joining')->nullable();
            $table->date('confirmation_date')->nullable();
            $table->boolean('probation_applicable')->default(false);
            $table->unsignedInteger('probation_period_days')->nullable();
            $table->date('probation_end_date')->nullable();

            $table->foreignId('department_id')->nullable()->constrained('departments')->restrictOnDelete();
            $table->foreignId('section_id')->nullable()->constrained('sections')->restrictOnDelete();
            $table->foreignId('designation_id')->nullable()->constrained('designations')->restrictOnDelete();
            $table->foreignId('reporting_manager_id')->nullable()->constrained('employees')->nullOnDelete();

            $table->string('shift_type');
            $table->foreignId('fixed_shift_id')->nullable()->constrained('shifts')->restrictOnDelete();
            $table->text('shift_type_override_reason')->nullable();

            $table->foreignId('contractor_id')->nullable()->constrained('contractors')->restrictOnDelete();
            $table->foreignId('contractor_branch_engagement_id')->nullable()->constrained('contractor_branch_engagements')->restrictOnDelete();

            $table->string('biometric_identifier')->nullable()->unique();

            $table->boolean('attendance_applicable')->default(true);
            $table->boolean('leave_applicable')->default(true);
            $table->boolean('payroll_applicable')->default(true);
            $table->boolean('overtime_applicable')->default(false);
            $table->text('applicability_override_reason')->nullable();

            $table->string('status')->default('draft');
            $table->timestamp('registration_completed_at')->nullable();
            $table->timestamp('activated_at')->nullable();
            $table->timestamp('inactivated_at')->nullable();
            $table->timestamp('separated_at')->nullable();

            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            $table->index('branch_id');
            $table->index('employee_type_id');
            $table->index('department_id');
            $table->index('section_id');
            $table->index('designation_id');
            $table->index('reporting_manager_id');
            $table->index('contractor_id');
            $table->index('contractor_branch_engagement_id');
            $table->index('shift_type');
            $table->index('fixed_shift_id');
            $table->index('date_of_joining');
            $table->index('status');
            $table->index(['first_name', 'last_name']);

            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('updated_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};
