<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->foreignId('country_id')->nullable()->after('fiscal_address')->constrained('countries')->nullOnDelete();
            $table->foreignId('state_id')->nullable()->after('country_id')->constrained('states')->nullOnDelete();
            $table->foreignId('city_id')->nullable()->after('state_id')->constrained('cities')->nullOnDelete();
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->foreignId('country_id')->nullable()->after('address')->constrained('countries')->nullOnDelete();
            $table->foreignId('state_id')->nullable()->after('country_id')->constrained('states')->nullOnDelete();
            $table->foreignId('city_id')->nullable()->after('state_id')->constrained('cities')->nullOnDelete();
        });

        $this->backfillTenantLocations();
        $this->backfillCustomerLocations();

        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn(['country', 'state', 'city']);
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn(['country', 'state', 'city']);
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->char('country', 2)->default('VE')->after('city_id');
            $table->string('state', 128)->nullable()->after('country');
            $table->string('city', 128)->nullable()->after('state');
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->char('country', 2)->default('VE')->after('city_id');
            $table->string('state', 128)->nullable()->after('country');
            $table->string('city', 128)->nullable()->after('state');
        });

        $this->restoreTenantLocationStrings();
        $this->restoreCustomerLocationStrings();

        Schema::table('tenants', function (Blueprint $table) {
            $table->dropConstrainedForeignId('city_id');
            $table->dropConstrainedForeignId('state_id');
            $table->dropConstrainedForeignId('country_id');
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->dropConstrainedForeignId('city_id');
            $table->dropConstrainedForeignId('state_id');
            $table->dropConstrainedForeignId('country_id');
        });
    }

    private function backfillTenantLocations(): void
    {
        $tenants = DB::table('tenants')
            ->select('id', 'country', 'state', 'city')
            ->get();

        foreach ($tenants as $tenant) {
            $countryId = $this->resolveCountryId($tenant->country);
            $stateId = $this->resolveStateId($countryId, $tenant->state);
            $cityId = $this->resolveCityId($countryId, $stateId, $tenant->city);

            DB::table('tenants')
                ->where('id', $tenant->id)
                ->update([
                    'country_id' => $countryId,
                    'state_id' => $stateId,
                    'city_id' => $cityId,
                ]);
        }
    }

    private function backfillCustomerLocations(): void
    {
        $customers = DB::table('customers')
            ->select('id', 'country', 'state', 'city')
            ->get();

        foreach ($customers as $customer) {
            $countryId = $this->resolveCountryId($customer->country);
            $stateId = $this->resolveStateId($countryId, $customer->state);
            $cityId = $this->resolveCityId($countryId, $stateId, $customer->city);

            DB::table('customers')
                ->where('id', $customer->id)
                ->update([
                    'country_id' => $countryId,
                    'state_id' => $stateId,
                    'city_id' => $cityId,
                ]);
        }
    }

    private function restoreTenantLocationStrings(): void
    {
        $rows = DB::table('tenants')
            ->leftJoin('countries', 'countries.id', '=', 'tenants.country_id')
            ->leftJoin('states', 'states.id', '=', 'tenants.state_id')
            ->leftJoin('cities', 'cities.id', '=', 'tenants.city_id')
            ->select('tenants.id', 'countries.iso2 as country_iso2', 'states.name as state_name', 'cities.name as city_name')
            ->get();

        foreach ($rows as $row) {
            DB::table('tenants')
                ->where('id', $row->id)
                ->update([
                    'country' => strtoupper((string) ($row->country_iso2 ?? 'VE')),
                    'state' => $row->state_name,
                    'city' => $row->city_name,
                ]);
        }
    }

    private function restoreCustomerLocationStrings(): void
    {
        $rows = DB::table('customers')
            ->leftJoin('countries', 'countries.id', '=', 'customers.country_id')
            ->leftJoin('states', 'states.id', '=', 'customers.state_id')
            ->leftJoin('cities', 'cities.id', '=', 'customers.city_id')
            ->select('customers.id', 'countries.iso2 as country_iso2', 'states.name as state_name', 'cities.name as city_name')
            ->get();

        foreach ($rows as $row) {
            DB::table('customers')
                ->where('id', $row->id)
                ->update([
                    'country' => strtoupper((string) ($row->country_iso2 ?? 'VE')),
                    'state' => $row->state_name,
                    'city' => $row->city_name,
                ]);
        }
    }

    private function resolveCountryId(?string $country): ?int
    {
        if (! is_string($country) || $country === '') {
            return null;
        }

        $country = strtoupper(trim($country));

        return DB::table('countries')->where('iso2', $country)->value('id');
    }

    private function resolveStateId(?int $countryId, ?string $state): ?int
    {
        if ($countryId === null || ! is_string($state) || trim($state) === '') {
            return null;
        }

        return DB::table('states')
            ->where('country_id', $countryId)
            ->whereRaw('LOWER(name) = ?', [mb_strtolower(trim($state))])
            ->value('id');
    }

    private function resolveCityId(?int $countryId, ?int $stateId, ?string $city): ?int
    {
        if (! is_string($city) || trim($city) === '') {
            return null;
        }

        $query = DB::table('cities')
            ->whereRaw('LOWER(name) = ?', [mb_strtolower(trim($city))]);

        if ($stateId !== null) {
            $query->where('state_id', $stateId);
        } elseif ($countryId !== null) {
            $query->where('country_id', $countryId);
        }

        return $query->value('id');
    }
};
