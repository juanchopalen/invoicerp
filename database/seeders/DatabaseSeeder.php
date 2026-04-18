<?php

namespace Database\Seeders;

use App\Models\City;
use App\Models\Country;
use App\Models\State;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        if (Country::query()->count() === 0) {
            $this->call(WorldTableSeeder::class);
        }
        $this->call(TenantSeeder::class);
        $this->call(CustomerSeeder::class);
        $this->call(ProductSeeder::class);

        $tenant = Tenant::query()->where('slug', 'default')->first();
        if ($tenant === null) {
            $country = Country::query()->where('iso2', 'VE')->first();
            $state = $country instanceof Country
                ? State::query()->where('country_id', $country->getKey())->orderBy('name')->first()
                : null;
            $city = $state instanceof State
                ? City::query()->where('state_id', $state->getKey())->orderBy('name')->first()
                : null;

            $tenant = Tenant::query()->create([
                'name' => 'Default tenant',
                'slug' => 'default',
                'legal_name' => 'Default tenant, C.A.',
                'rif' => 'J-00000000-0',
                'trade_name' => 'Default tenant',
                'fiscal_address' => 'Dirección fiscal por definir',
                'country_id' => $country?->getKey(),
                'state_id' => $state?->getKey(),
                'city_id' => $city?->getKey(),
                'municipality' => 'Libertador',
                'phone' => '+58-000-0000000',
                'email' => 'fiscal@invoicerp.net',
                'is_special_taxpayer' => false,
            ]);
        }

        User::query()->updateOrCreate(
            ['email' => 'admin@invoicerp.net'],
            [
                'name' => 'Juan Palencia',
                'password' => Hash::make('password'),
                'tenant_id' => $tenant->getKey(),
            ],
        );
    }
}
