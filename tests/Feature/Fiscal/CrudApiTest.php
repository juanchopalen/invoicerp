<?php

namespace Tests\Feature\Fiscal;

use App\Models\City;
use App\Models\Country;
use App\Models\Customer;
use App\Models\State;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CrudApiTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array{0: Country, 1: State, 2: City}
     */
    private function createWorldLocation(): array
    {
        $country = Country::query()->firstOrCreate(
            ['iso2' => 'VE'],
            [
                'name' => 'Venezuela',
                'iso3' => 'VEN',
                'is_active' => true,
            ],
        );
        $state = State::query()->firstOrCreate(
            ['country_id' => $country->getKey(), 'name' => 'Miranda'],
            ['is_active' => true],
        );
        $city = City::query()->firstOrCreate(
            ['country_id' => $country->getKey(), 'state_id' => $state->getKey(), 'name' => 'Caracas'],
            ['is_active' => true],
        );

        return [$country, $state, $city];
    }

    private function bearerForTenantUser(Tenant $tenant): string
    {
        $user = User::factory()->create(['tenant_id' => $tenant->getKey()]);

        return $user->createToken('test-crud')->plainTextToken;
    }

    public function test_customer_crud_via_api(): void
    {
        [$country, $state, $city] = $this->createWorldLocation();
        $tenant = Tenant::query()->create([
            'name' => 'Acme CRUD',
            'slug' => 'acme-crud',
            'legal_name' => 'Acme C.A.',
            'rif' => 'J-90000000-0',
            'fiscal_address' => 'Calle 1',
            'country_id' => $country->getKey(),
            'state_id' => $state->getKey(),
            'city_id' => $city->getKey(),
            'municipality' => 'Libertador',
        ]);
        $token = $this->bearerForTenantUser($tenant);

        $payload = [
            'legal_name' => 'Cliente API',
            'tax_id' => 'J-11111111-1',
            'address' => 'Av. Test',
            'country_id' => $country->getKey(),
            'state_id' => $state->getKey(),
            'city_id' => $city->getKey(),
            'municipality' => 'Chacao',
        ];

        $created = $this->postJson('/api/v1/customers', $payload, [
            'Authorization' => 'Bearer '.$token,
        ]);
        $created->assertCreated();
        $id = $created->json('data.id');
        $this->assertDatabaseHas('customers', ['id' => $id, 'legal_name' => 'Cliente API']);

        $this->getJson('/api/v1/customers/'.$id, [
            'Authorization' => 'Bearer '.$token,
        ])->assertOk()->assertJsonPath('data.legal_name', 'Cliente API');

        $updated = array_merge($payload, ['legal_name' => 'Cliente API 2']);
        $this->putJson('/api/v1/customers/'.$id, $updated, [
            'Authorization' => 'Bearer '.$token,
        ])->assertOk()->assertJsonPath('data.legal_name', 'Cliente API 2');

        $this->deleteJson('/api/v1/customers/'.$id, [], [
            'Authorization' => 'Bearer '.$token,
        ])->assertNoContent();
        $this->assertDatabaseMissing('customers', ['id' => $id]);
    }

    public function test_product_crud_via_api(): void
    {
        [$country, $state, $city] = $this->createWorldLocation();
        $tenant = Tenant::query()->create([
            'name' => 'Acme Prod',
            'slug' => 'acme-prod',
            'legal_name' => 'Acme Prod C.A.',
            'rif' => 'J-90000001-8',
            'fiscal_address' => 'Calle 2',
            'country_id' => $country->getKey(),
            'state_id' => $state->getKey(),
            'city_id' => $city->getKey(),
            'municipality' => 'Libertador',
        ]);
        $token = $this->bearerForTenantUser($tenant);

        $payload = [
            'name' => 'Item A',
            'unit_price' => '10.5000',
            'tax_rate' => '16.0000',
            'currency' => 'VES',
        ];

        $created = $this->postJson('/api/v1/products', $payload, [
            'Authorization' => 'Bearer '.$token,
        ]);
        $created->assertCreated();
        $id = $created->json('data.id');

        $this->putJson('/api/v1/products/'.$id, array_merge($payload, ['name' => 'Item B']), [
            'Authorization' => 'Bearer '.$token,
        ])->assertOk()->assertJsonPath('data.name', 'Item B');

        $this->deleteJson('/api/v1/products/'.$id, [], [
            'Authorization' => 'Bearer '.$token,
        ])->assertNoContent();
    }

    public function test_tenant_crud_via_api(): void
    {
        [$country, $state, $city] = $this->createWorldLocation();
        $tenant = Tenant::query()->create([
            'name' => 'Seed',
            'slug' => 'seed-tenant',
            'legal_name' => 'Seed C.A.',
            'rif' => 'J-90000002-6',
            'fiscal_address' => 'Calle 3',
            'country_id' => $country->getKey(),
            'state_id' => $state->getKey(),
            'city_id' => $city->getKey(),
            'municipality' => 'Libertador',
        ]);
        $token = $this->bearerForTenantUser($tenant);

        $payload = [
            'name' => 'Nueva',
            'slug' => 'nueva-co',
            'legal_name' => 'Nueva C.A.',
            'rif' => 'J-90000003-4',
            'fiscal_address' => 'Zona industrial',
            'country_id' => $country->getKey(),
            'state_id' => $state->getKey(),
            'city_id' => $city->getKey(),
            'municipality' => 'Petare',
            'is_special_taxpayer' => false,
        ];

        $created = $this->postJson('/api/v1/tenants', $payload, [
            'Authorization' => 'Bearer '.$token,
        ]);
        $created->assertCreated();
        $id = $created->json('data.id');

        $this->putJson('/api/v1/tenants/'.$id, array_merge($payload, ['name' => 'Nueva 2']), [
            'Authorization' => 'Bearer '.$token,
        ])->assertOk()->assertJsonPath('data.name', 'Nueva 2');

        $this->deleteJson('/api/v1/tenants/'.$id, [], [
            'Authorization' => 'Bearer '.$token,
        ])->assertNoContent();
    }

    public function test_api_client_create_and_update_via_api(): void
    {
        [$country, $state, $city] = $this->createWorldLocation();
        $tenant = Tenant::query()->create([
            'name' => 'Acme API Client',
            'slug' => 'acme-api-client',
            'legal_name' => 'Acme API Client C.A.',
            'rif' => 'J-90000004-2',
            'fiscal_address' => 'Calle 4',
            'country_id' => $country->getKey(),
            'state_id' => $state->getKey(),
            'city_id' => $city->getKey(),
            'municipality' => 'Libertador',
        ]);
        $token = $this->bearerForTenantUser($tenant);

        $created = $this->postJson('/api/v1/api-clients', [
            'name' => 'Integración',
            'status' => 'active',
        ], [
            'Authorization' => 'Bearer '.$token,
        ]);
        $created->assertCreated();
        $created->assertJsonStructure(['data', 'api_key_plain']);
        $id = $created->json('data.id');

        $this->putJson('/api/v1/api-clients/'.$id, [
            'name' => 'Integración 2',
            'status' => 'inactive',
        ], [
            'Authorization' => 'Bearer '.$token,
        ])->assertOk()->assertJsonPath('data.name', 'Integración 2');
    }

    public function test_documents_index_and_audit_logs_require_auth_context(): void
    {
        [$country, $state, $city] = $this->createWorldLocation();
        $tenant = Tenant::query()->create([
            'name' => 'Acme Index',
            'slug' => 'acme-index',
            'legal_name' => 'Acme Index C.A.',
            'rif' => 'J-90000005-0',
            'fiscal_address' => 'Calle 5',
            'country_id' => $country->getKey(),
            'state_id' => $state->getKey(),
            'city_id' => $city->getKey(),
            'municipality' => 'Libertador',
        ]);
        $token = $this->bearerForTenantUser($tenant);

        $this->getJson('/api/v1/documents', [
            'Authorization' => 'Bearer '.$token,
        ])->assertOk();

        $this->getJson('/api/v1/audit-logs', [
            'Authorization' => 'Bearer '.$token,
        ])->assertOk();
    }

    public function test_customer_cannot_access_other_tenant_customer(): void
    {
        [$country, $state, $city] = $this->createWorldLocation();
        $tenantA = Tenant::query()->create([
            'name' => 'A',
            'slug' => 'tenant-a',
            'legal_name' => 'A C.A.',
            'rif' => 'J-90000006-8',
            'fiscal_address' => 'A',
            'country_id' => $country->getKey(),
            'state_id' => $state->getKey(),
            'city_id' => $city->getKey(),
            'municipality' => 'Libertador',
        ]);
        $tenantB = Tenant::query()->create([
            'name' => 'B',
            'slug' => 'tenant-b',
            'legal_name' => 'B C.A.',
            'rif' => 'J-90000007-6',
            'fiscal_address' => 'B',
            'country_id' => $country->getKey(),
            'state_id' => $state->getKey(),
            'city_id' => $city->getKey(),
            'municipality' => 'Libertador',
        ]);
        $customerB = Customer::query()->create([
            'tenant_id' => $tenantB->getKey(),
            'legal_name' => 'Cliente B',
            'tax_id' => 'J-22222222-2',
            'address' => 'Dir',
            'country_id' => $country->getKey(),
            'state_id' => $state->getKey(),
            'city_id' => $city->getKey(),
            'municipality' => 'X',
        ]);
        $tokenA = $this->bearerForTenantUser($tenantA);

        $this->getJson('/api/v1/customers/'.$customerB->getKey(), [
            'Authorization' => 'Bearer '.$tokenA,
        ])->assertNotFound();
    }
}
