<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\Tenant;
use Illuminate\Database\Seeder;

class CustomerSeeder extends Seeder
{
    public function run(): void
    {
        $tenants = Tenant::query()->with(['country', 'state', 'city'])->get();

        foreach ($tenants as $tenant) {
            $customers = [
                [
                    'legal_name' => 'Cliente Mostrador',
                    'tax_id' => 'V-12345678',
                    'email' => 'mostrador@cliente.ve',
                    'phone' => '+58-412-0000001',
                    'address' => 'Av. Principal, local 1',
                    'municipality' => $tenant->municipality ?? 'Libertador',
                ],
                [
                    'legal_name' => 'Inversiones Delta, C.A.',
                    'tax_id' => 'J-30999888-7',
                    'email' => 'compras@delta.ve',
                    'phone' => '+58-212-0000002',
                    'address' => 'Centro Empresarial Delta, piso 2',
                    'municipality' => $tenant->municipality ?? 'Libertador',
                ],
            ];

            foreach ($customers as $row) {
                Customer::query()->updateOrCreate(
                    [
                        'tenant_id' => $tenant->getKey(),
                        'tax_id' => $row['tax_id'],
                    ],
                    [
                        ...$row,
                        'tenant_id' => $tenant->getKey(),
                        'country_id' => $tenant->country_id,
                        'state_id' => $tenant->state_id,
                        'city_id' => $tenant->city_id,
                    ],
                );
            }
        }
    }
}
