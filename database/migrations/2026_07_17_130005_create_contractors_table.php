<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contractors', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('organization_id')->constrained('organizations')->restrictOnDelete();

            $table->string('contractor_code');
            $table->string('legal_name');
            $table->string('trade_name')->nullable();
            $table->string('contractor_type')->nullable();

            $table->string('contact_person_name');
            $table->string('primary_phone');
            $table->string('alternate_phone')->nullable();
            $table->string('primary_email')->nullable();

            $table->string('address_line_1');
            $table->string('address_line_2')->nullable();
            $table->string('city');
            $table->string('district')->nullable();
            $table->string('state');
            $table->string('country')->default('India');
            $table->string('postal_code');

            $table->string('pan_number')->nullable();
            $table->string('gstin')->nullable();
            $table->string('pf_registration_number')->nullable();
            $table->string('esi_registration_number')->nullable();

            $table->string('labour_licence_number')->nullable();
            $table->date('labour_licence_valid_from')->nullable();
            $table->date('labour_licence_valid_to')->nullable();

            $table->text('description')->nullable();
            $table->string('status')->default('active');

            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            $table->unique(['organization_id', 'contractor_code']);
            $table->unique(['organization_id', 'legal_name']);
            $table->unique(['organization_id', 'pan_number']);
            $table->unique(['organization_id', 'gstin']);
            $table->index('contractor_code');
            $table->index('legal_name');
            $table->index('pan_number');
            $table->index('gstin');
            $table->index('status');
            $table->index('labour_licence_valid_to');

            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('updated_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contractors');
    }
};
