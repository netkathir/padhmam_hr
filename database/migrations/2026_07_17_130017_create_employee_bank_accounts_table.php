<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_bank_accounts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();

            $table->string('account_holder_name')->nullable();
            $table->string('bank_name')->nullable();
            $table->string('branch_name')->nullable();
            // Encrypted at the application layer (see EmployeeBankAccount
            // model casts) — stored as `text` to accommodate ciphertext length.
            $table->text('account_number')->nullable();
            $table->string('account_type')->nullable();
            $table->string('ifsc_code')->nullable();
            $table->boolean('is_primary')->default(false);
            $table->string('status')->default('active');

            $table->timestamps();

            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_bank_accounts');
    }
};
