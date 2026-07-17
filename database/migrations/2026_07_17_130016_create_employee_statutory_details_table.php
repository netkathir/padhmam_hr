<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_statutory_details', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('employee_id')->unique()->constrained('employees')->cascadeOnDelete();

            // Encrypted at the application layer (see EmployeeStatutoryDetail
            // model casts) — stored as `text` to accommodate ciphertext length.
            $table->text('aadhaar_number')->nullable();
            $table->string('pan_number')->nullable();
            $table->string('uan_number')->nullable();
            $table->string('pf_number')->nullable();
            $table->string('esi_number')->nullable();

            $table->boolean('professional_tax_applicable')->default(true);
            $table->boolean('pf_applicable')->default(true);
            $table->boolean('esi_applicable')->default(true);
            $table->boolean('tds_applicable')->default(true);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_statutory_details');
    }
};
