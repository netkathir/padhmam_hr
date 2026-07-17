<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contractor_branch_engagements', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('contractor_id')->constrained('contractors')->restrictOnDelete();
            $table->foreignId('branch_id')->constrained('branches')->restrictOnDelete();

            $table->string('agreement_number')->nullable();
            $table->date('agreement_date')->nullable();
            $table->date('contract_start_date');
            $table->date('contract_end_date')->nullable();
            $table->unsignedInteger('maximum_labour_count')->nullable();

            $table->string('branch_labour_licence_number')->nullable();
            $table->date('branch_licence_valid_from')->nullable();
            $table->date('branch_licence_valid_to')->nullable();

            $table->string('contact_person_name')->nullable();
            $table->string('contact_person_phone')->nullable();
            $table->text('remarks')->nullable();
            $table->string('status')->default('active');

            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            $table->unique(['contractor_id', 'branch_id']);
            $table->index('status');
            $table->index('contract_start_date');
            $table->index('contract_end_date');
            $table->index('branch_licence_valid_to');

            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('updated_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contractor_branch_engagements');
    }
};
