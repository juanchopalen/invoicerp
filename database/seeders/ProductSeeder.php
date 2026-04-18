<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\Tenant;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $tenants = Tenant::query()->get();

        foreach ($tenants as $tenant) {
            $products = [
                [
                    'sku' => 'PROD-001',
                    'serial' => '750100001001',
                    'name' => 'Taladro 1/2"',
                    'description' => 'Taladro percutor industrial',
                    'unit_price' => '45.0000',
                    'tax_rate' => '16.0000',
                    'currency' => 'VES',
                    'unit' => 'und',
                ],
                [
                    'sku' => 'PROD-002',
                    'serial' => '750100001002',
                    'name' => 'Caja de tornillos',
                    'description' => 'Caja x 100 unidades',
                    'unit_price' => '8.5000',
                    'tax_rate' => '16.0000',
                    'currency' => 'VES',
                    'unit' => 'caja',
                ],
                [
                    'sku' => 'PROD-003',
                    'serial' => '750100001003',
                    'name' => 'Guantes de seguridad',
                    'description' => 'Par de guantes industriales',
                    'unit_price' => '3.2500',
                    'tax_rate' => '16.0000',
                    'currency' => 'VES',
                    'unit' => 'par',
                ],
            ];

            foreach ($products as $row) {
                Product::query()->updateOrCreate(
                    [
                        'tenant_id' => $tenant->getKey(),
                        'sku' => $row['sku'],
                    ],
                    [
                        ...$row,
                        'tenant_id' => $tenant->getKey(),
                    ],
                );
            }
        }
    }
}
