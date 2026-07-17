<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('organizations', function (Blueprint $table): void {
            $table->id();
            $table->string('organization_code')->unique();
            $table->string('legal_name');
            $table->string('display_name');
            $table->string('business_type')->nullable();
            $table->date('incorporation_date')->nullable();
            $table->unsignedTinyInteger('financial_year_start_month')->default(4);

            $table->string('address_line_1');
            $table->string('address_line_2')->nullable();
            $table->string('city');
            $table->string('district')->nullable();
            $table->string('state');
            $table->string('country')->default('India');
            $table->string('postal_code');

            $table->string('primary_phone')->nullable();
            $table->string('alternate_phone')->nullable();
            $table->string('primary_email')->nullable();
            $table->string('website')->nullable();

            $table->string('pan_number')->nullable();
            $table->string('tan_number')->nullable();
            $table->string('gstin')->nullable();
            $table->string('pf_registration_number')->nullable();
            $table->string('esi_registration_number')->nullable();
            $table->string('professional_tax_number')->nullable();

            $table->string('logo_path')->nullable();
            $table->string('status')->default('active')->index();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('organizations');
    }
};
