<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_number_reservations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('employee_number_rule_id')->constrained('employee_number_rules')->restrictOnDelete();
            $table->foreignId('branch_id')->constrained('branches')->restrictOnDelete();
            $table->foreignId('employee_type_id')->constrained('employee_types')->restrictOnDelete();

            $table->string('sequence_period');
            $table->unsignedBigInteger('serial_number');
            $table->string('generated_employee_number');
            $table->string('reservation_token');

            $table->unsignedBigInteger('reserved_by')->nullable();
            $table->timestamp('reserved_at');
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('finalized_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->string('status')->default('reserved');

            $table->timestamps();

            $table->unique('generated_employee_number');
            $table->unique('reservation_token');
            $table->index('status');
            $table->index('expires_at');
            $table->index('branch_id');
            $table->index('employee_type_id');

            $table->foreign('reserved_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_number_reservations');
    }
};
