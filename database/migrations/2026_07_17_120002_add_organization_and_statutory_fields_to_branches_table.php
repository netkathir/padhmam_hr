<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('branches', function (Blueprint $table): void {
            $table->renameColumn('code', 'branch_code');
            $table->renameColumn('name', 'branch_name');
        });

        Schema::table('branches', function (Blueprint $table): void {
            $table->foreignId('organization_id')->nullable()->after('id')->constrained()->restrictOnDelete();
            $table->string('short_name')->nullable()->after('branch_name');
            $table->string('branch_type')->default('office')->after('short_name')->index();
            $table->boolean('is_head_office')->default(false)->after('branch_type')->index();
            $table->string('district')->nullable()->after('city');
            $table->string('country')->default('India')->after('state');
            $table->string('alternate_phone')->nullable()->after('phone');
            $table->string('contact_person_name')->nullable()->after('email');
            $table->string('contact_person_phone')->nullable()->after('contact_person_name');
            $table->string('gstin')->nullable()->after('contact_person_phone');
            $table->string('pf_sub_code')->nullable()->after('gstin');
            $table->string('esi_sub_code')->nullable()->after('pf_sub_code');
            $table->string('professional_tax_number')->nullable()->after('esi_sub_code');
            $table->string('establishment_code')->nullable()->after('professional_tax_number');
            $table->string('timezone')->default('Asia/Kolkata')->after('establishment_code');
            $table->unsignedInteger('display_order')->default(0)->after('timezone')->index();
        });

        $organizationId = DB::table('organizations')->value('id');

        if (! $organizationId) {
            $organizationId = DB::table('organizations')->insertGetId([
                'organization_code' => 'PADMAM',
                'legal_name' => 'Padmam Industries',
                'display_name' => 'Padmam Industries',
                'address_line_1' => 'Registered Office',
                'city' => 'Chennai',
                'state' => 'Tamil Nadu',
                'country' => 'India',
                'postal_code' => '600001',
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        DB::table('branches')->whereNull('organization_id')->update(['organization_id' => $organizationId]);
        DB::table('branches')->where('branch_code', 'HO')->update([
            'is_head_office' => true,
            'branch_type' => 'head_office',
        ]);

        Schema::table('branches', function (Blueprint $table): void {
            $table->unsignedBigInteger('organization_id')->nullable(false)->change();
        });

        Schema::table('branches', function (Blueprint $table): void {
            $table->dropUnique('branches_code_unique');
            $table->unique(['organization_id', 'branch_code']);
        });
    }

    public function down(): void
    {
        Schema::table('branches', function (Blueprint $table): void {
            $table->dropUnique(['organization_id', 'branch_code']);
            $table->dropConstrainedForeignId('organization_id');
            $table->dropColumn([
                'short_name',
                'branch_type',
                'is_head_office',
                'district',
                'country',
                'alternate_phone',
                'contact_person_name',
                'contact_person_phone',
                'gstin',
                'pf_sub_code',
                'esi_sub_code',
                'professional_tax_number',
                'establishment_code',
                'timezone',
                'display_order',
            ]);
        });

        Schema::table('branches', function (Blueprint $table): void {
            $table->renameColumn('branch_code', 'code');
            $table->renameColumn('branch_name', 'name');
        });

        Schema::table('branches', function (Blueprint $table): void {
            $table->unique('code');
        });
    }
};
