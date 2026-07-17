<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_documents', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();

            $table->string('document_type');
            $table->string('document_number')->nullable();
            $table->date('issued_date')->nullable();
            $table->date('expiry_date')->nullable();

            $table->string('file_path')->nullable();
            $table->string('original_file_name')->nullable();
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('file_size')->nullable();

            $table->text('remarks')->nullable();
            $table->string('status')->default('active');

            $table->unsignedBigInteger('uploaded_by')->nullable();
            $table->timestamps();

            $table->index('employee_id');
            $table->index('document_type');
            $table->index('expiry_date');
            $table->index('status');

            $table->foreign('uploaded_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_documents');
    }
};
