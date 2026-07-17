<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_shift_assignments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('branch_id')->constrained('branches')->restrictOnDelete();
            $table->foreignId('employee_id')->constrained('employees')->restrictOnDelete();
            $table->foreignId('shift_id')->constrained('shifts')->restrictOnDelete();

            $table->string('assignment_type');
            $table->date('effective_from');
            $table->date('effective_to')->nullable();
            $table->boolean('is_current')->default(false);

            $table->text('assignment_reason')->nullable();
            $table->string('change_reference')->nullable();
            $table->string('status')->default('scheduled');
            $table->timestamp('cancelled_at')->nullable();
            $table->text('cancellation_reason')->nullable();

            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            $table->index('branch_id');
            $table->index('employee_id');
            $table->index('shift_id');
            $table->index('assignment_type');
            $table->index('effective_from');
            $table->index('effective_to');
            $table->index('status');
            $table->index('is_current');
            $table->index(['employee_id', 'effective_from', 'effective_to'], 'esa_employee_period_idx');
            $table->index(['branch_id', 'status'], 'esa_branch_status_idx');
            $table->index(['shift_id', 'effective_from'], 'esa_shift_from_idx');

            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('updated_by')->references('id')->on('users')->nullOnDelete();
        });

        $this->backfillInitialFixedAssignments();
    }

    /**
     * Employee Registration (prior module) already sets employees.shift_type
     * and employees.fixed_shift_id directly with no assignment history.
     * This creates exactly one initial Fixed assignment per such Employee so
     * the new assignments table becomes the source of truth going forward,
     * without altering the Employee row itself. Idempotent-safe: only runs
     * once as part of this migration, and only inserts rows where none
     * already exist for that employee (defensive, since the table is brand
     * new here anyway).
     */
    private function backfillInitialFixedAssignments(): void
    {
        $today = now()->toDateString();

        $employees = DB::table('employees')
            ->whereNotNull('fixed_shift_id')
            ->where('shift_type', 'fixed')
            ->whereIn('status', ['active', 'inactive'])
            ->get(['id', 'branch_id', 'fixed_shift_id', 'date_of_joining']);

        foreach ($employees as $employee) {
            $exists = DB::table('employee_shift_assignments')->where('employee_id', $employee->id)->exists();

            if ($exists) {
                continue;
            }

            $effectiveFrom = $employee->date_of_joining ?? $today;
            $status = $effectiveFrom > $today ? 'scheduled' : 'active';

            DB::table('employee_shift_assignments')->insert([
                'branch_id' => $employee->branch_id,
                'employee_id' => $employee->id,
                'shift_id' => $employee->fixed_shift_id,
                'assignment_type' => 'fixed',
                'effective_from' => $effectiveFrom,
                'effective_to' => null,
                'is_current' => true,
                'assignment_reason' => 'Backfilled from initial Employee Registration.',
                'status' => $status,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_shift_assignments');
    }
};
