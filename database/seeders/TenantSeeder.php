<?php

namespace Database\Seeders;

use App\Models\City;
use App\Models\Country;
use App\Models\State;
use App\Models\Tenant;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class TenantSeeder extends Seeder
{
    public function run(): void
    {
        $country = Country::query()->where('iso2', 'VE')->first();
        $state = $country instanceof Country
            ? State::query()->where('country_id', $country->getKey())->orderBy('name')->first()
            : null;
        $city = $state instanceof State
            ? City::query()->where('state_id', $state->getKey())->orderBy('name')->first()
            : null;

        $tenants = [
            [
                'name' => 'Default tenant',
                'slug' => 'default',
                'legal_name' => 'Default tenant, C.A.',
                'rif' => 'J-00000000-0',
                'trade_name' => 'Default tenant',
                'fiscal_address' => 'Dirección fiscal por definir',
                'municipality' => 'Libertador',
                'phone' => '+58-000-0000000',
                'email' => 'fiscal@invoicerp.net',
                'is_special_taxpayer' => false,
            ],
            [
                'name' => 'Ferretería Centro',
                'slug' => 'ferreteria-centro',
                'legal_name' => 'Ferretería Centro, C.A.',
                'rif' => 'J-41234567-1',
                'trade_name' => 'Ferretería Centro',
                'fiscal_address' => 'Av. Bolívar, Local 12',
                'municipality' => 'Libertador',
                'phone' => '+58-212-5550101',
                'email' => 'admin@ferreteriacentro.ve',
                'is_special_taxpayer' => false,
            ],
            [
                'name' => 'Distribuidora Andina',
                'slug' => 'distribuidora-andina',
                'legal_name' => 'Distribuidora Andina, C.A.',
                'rif' => 'J-42345678-2',
                'trade_name' => 'Distribuidora Andina',
                'fiscal_address' => 'Zona Industrial, Galpón 4',
                'municipality' => 'Sucre',
                'phone' => '+58-414-5550202',
                'email' => 'info@andina.ve',
                'is_special_taxpayer' => true,
                'special_taxpayer_resolution' => 'SENIAT-CE-2026-001',
            ],
        ];

        foreach ($tenants as $row) {
            $slug = Str::slug($row['slug'] ?? $row['name']);

            Tenant::query()->updateOrCreate(
                ['slug' => $slug],
                [
                    ...$row,
                    'slug' => $slug,
                    'country_id' => $country?->getKey(),
                    'state_id' => $state?->getKey(),
                    'city_id' => $city?->getKey(),
                ],
            );
        }
    }
}
