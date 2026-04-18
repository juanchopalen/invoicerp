<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->string('legal_name')->nullable()->after('slug');
            $table->string('rif', 32)->nullable()->after('legal_name');
            $table->string('trade_name')->nullable()->after('rif');
            $table->text('fiscal_address')->nullable()->after('trade_name');

            $table->string('state', 128)->nullable()->after('fiscal_address');
            $table->string('municipality', 128)->nullable()->after('state');
            $table->string('city', 128)->nullable()->after('municipality');
            $table->char('country', 2)->default('VE')->after('city');

            $table->string('phone', 64)->nullable()->after('country');
            $table->string('email')->nullable()->after('phone');

            $table->boolean('is_special_taxpayer')->default(false)->after('email');
            $table->string('special_taxpayer_resolution')->nullable()->after('is_special_taxpayer');
            $table->string('withholding_agent_number')->nullable()->after('special_taxpayer_resolution');

            $table->string('economic_activity')->nullable()->after('withholding_agent_number');
            $table->string('establishment_code', 10)->nullable()->after('economic_activity');
            $table->string('emission_point', 10)->nullable()->after('establishment_code');

            $table->unique('rif');
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropUnique(['rif']);
            $table->dropColumn([
                'legal_name',
                'rif',
                'trade_name',
                'fiscal_address',
                'state',
                'municipality',
                'city',
                'country',
                'phone',
                'email',
                'is_special_taxpayer',
                'special_taxpayer_resolution',
                'withholding_agent_number',
                'economic_activity',
                'establishment_code',
                'emission_point',
            ]);
        });
    }
};
