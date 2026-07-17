<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shifts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('branch_id')->constrained('branches')->restrictOnDelete();

            $table->string('shift_code');
            $table->string('shift_name');
            $table->string('short_name')->nullable();
            $table->string('shift_type');

            $table->time('start_time');
            $table->time('end_time');
            $table->boolean('is_overnight')->default(false);

            $table->unsignedInteger('gross_shift_minutes');
            $table->unsignedInteger('break_duration_minutes')->default(0);
            $table->unsignedInteger('scheduled_work_minutes');

            $table->unsignedInteger('early_entry_allowed_minutes')->default(0);
            $table->unsignedInteger('late_entry_grace_minutes')->default(0);
            $table->unsignedInteger('early_exit_grace_minutes')->default(0);
            $table->unsignedInteger('late_exit_allowed_minutes')->default(0);

            $table->unsignedInteger('minimum_half_day_minutes')->nullable();
            $table->unsignedInteger('minimum_full_day_minutes')->nullable();

            $table->boolean('overtime_applicable')->default(false);
            $table->unsignedInteger('overtime_start_after_minutes')->nullable();

            $table->json('applicable_days');

            $table->date('effective_from');
            $table->date('effective_to')->nullable();

            $table->string('color_code')->nullable();
            $table->text('description')->nullable();
            $table->unsignedInteger('display_order')->default(0);
            $table->string('status')->default('draft');

            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            $table->unique(['branch_id', 'shift_code']);
            $table->unique(['branch_id', 'shift_name']);
            $table->index('branch_id');
            $table->index('shift_code');
            $table->index('shift_name');
            $table->index('shift_type');
            $table->index('start_time');
            $table->index('end_time');
            $table->index('is_overnight');
            $table->index('effective_from');
            $table->index('effective_to');
            $table->index('status');
            $table->index('display_order');

            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('updated_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shifts');
    }
};
