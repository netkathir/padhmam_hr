<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_separations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();

            $table->string('separation_type');
            $table->date('last_working_date');
            $table->text('separation_reason');
            $table->date('notice_date')->nullable();
            $table->text('remarks')->nullable();

            $table->unsignedBigInteger('processed_by')->nullable();
            $table->timestamps();

            $table->index('employee_id');
            $table->index('last_working_date');

            $table->foreign('processed_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_separations');
    }
};
