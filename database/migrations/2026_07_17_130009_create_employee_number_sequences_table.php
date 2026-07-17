<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_number_sequences', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('employee_number_rule_id')->constrained('employee_number_rules')->restrictOnDelete();

            $table->string('sequence_period');
            $table->unsignedBigInteger('last_issued_number')->default(0);
            $table->unsignedBigInteger('next_number');
            $table->timestamp('last_generated_at')->nullable();

            $table->timestamps();

            $table->unique(['employee_number_rule_id', 'sequence_period'], 'employee_number_sequences_rule_period_unique');
            $table->index('sequence_period');
            $table->index('last_generated_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_number_sequences');
    }
};
