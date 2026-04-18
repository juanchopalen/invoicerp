<?php

namespace Tests\Feature\Fiscal;

use App\Models\ApiClient;
use App\Models\AuditLog;
use App\Models\City;
use App\Models\Country;
use App\Models\Customer;
use App\Models\FiscalDocument;
use App\Models\State;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvoicErpApiTest extends TestCase
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

    private function createCustomer(Tenant $tenant): Customer
    {
        [$country, $state, $city] = $this->createWorldLocation();

        return Customer::query()->create([
            'tenant_id' => $tenant->getKey(),
            'legal_name' => 'Cliente Fiscal',
            'tax_id' => 'J-12345678-9',
            'address' => 'Av. Principal, edificio demo',
            'municipality' => 'Chacao',
            'country_id' => $country->getKey(),
            'state_id' => $state->getKey(),
            'city_id' => $city->getKey(),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function sampleEmitPayload(Tenant $tenant, string $externalReference = 'ext-1', ?Customer $customer = null): array
    {
        $customer ??= $this->createCustomer($tenant);

        return [
            'source_system' => 'test',
            'external_reference' => $externalReference,
            'document_type' => 'invoice',
            'currency' => 'VES',
            'schema_version' => 1,
            'customer_id' => $customer->getKey(),
            'items' => [
                [
                    'line_number' => 1,
                    'description' => 'Item',
                    'qty' => '1',
                    'unit_price' => '100.0000',
                    'tax_rate' => '0',
                    'line_subtotal' => '100.0000',
                    'line_tax' => '0.0000',
                    'line_total' => '100.0000',
                ],
            ],
        ];
    }

    public function test_emit_is_idempotent_for_same_payload(): void
    {
        $tenant = Tenant::query()->create(['name' => 'Acme', 'slug' => 'acme']);
        [$plain] = ApiClient::generateKeyPair();
        ApiClient::query()->create([
            'tenant_id' => $tenant->getKey(),
            'name' => 'Test client',
            'api_key' => ApiClient::hashKey($plain),
            'key_prefix' => explode('.', $plain, 2)[0],
            'status' => 'active',
        ]);

        $customer = $this->createCustomer($tenant);
        $payload = $this->sampleEmitPayload($tenant, 'idem-1', $customer);
        $first = $this->postJson('/api/v1/documents/emit', $payload, [
            'Authorization' => 'Bearer '.$plain,
        ]);
        $first->assertCreated();
        $second = $this->postJson('/api/v1/documents/emit', $payload, [
            'Authorization' => 'Bearer '.$plain,
        ]);
        $second->assertOk();
        $this->assertSame($first->json('id'), $second->json('id'));
    }

    public function test_emit_rejects_duplicate_line_numbers(): void
    {
        $tenant = Tenant::query()->create(['name' => 'Acme', 'slug' => 'acme-dup-lines']);
        [$plain] = ApiClient::generateKeyPair();
        ApiClient::query()->create([
            'tenant_id' => $tenant->getKey(),
            'name' => 'Test client',
            'api_key' => ApiClient::hashKey($plain),
            'key_prefix' => explode('.', $plain, 2)[0],
            'status' => 'active',
        ]);

        $payload = $this->sampleEmitPayload($tenant, 'dup-lines-1');
        $payload['items'][] = [
            'line_number' => 1,
            'description' => 'Second with same line',
            'qty' => '1',
            'unit_price' => '10.0000',
            'tax_rate' => '0',
            'line_subtotal' => '10.0000',
            'line_tax' => '0.0000',
            'line_total' => '10.0000',
        ];

        $response = $this->postJson('/api/v1/documents/emit', $payload, [
            'Authorization' => 'Bearer '.$plain,
        ]);
        $response->assertUnprocessable();
        $response->assertJsonFragment([
            'Cada ítem debe tener un line_number distinto dentro del documento; hay números de línea repetidos.',
        ]);
    }

    public function test_emit_returns_409_when_idempotency_key_reused_with_different_payload(): void
    {
        $tenant = Tenant::query()->create(['name' => 'Acme', 'slug' => 'acme-2']);
        [$plain] = ApiClient::generateKeyPair();
        ApiClient::query()->create([
            'tenant_id' => $tenant->getKey(),
            'name' => 'Test client',
            'api_key' => ApiClient::hashKey($plain),
            'key_prefix' => explode('.', $plain, 2)[0],
            'status' => 'active',
        ]);

        $customer = $this->createCustomer($tenant);
        $base = $this->sampleEmitPayload($tenant, 'conflict-1', $customer);
        $this->postJson('/api/v1/documents/emit', $base, [
            'Authorization' => 'Bearer '.$plain,
        ])->assertCreated();

        $changed = $base;
        $changed['items'][0]['description'] = 'Changed';

        $this->postJson('/api/v1/documents/emit', $changed, [
            'Authorization' => 'Bearer '.$plain,
        ])->assertStatus(409);
    }

    public function test_sequential_emits_increment_document_number_without_gaps(): void
    {
        $tenant = Tenant::query()->create(['name' => 'Acme', 'slug' => 'acme-3']);
        [$plain] = ApiClient::generateKeyPair();
        ApiClient::query()->create([
            'tenant_id' => $tenant->getKey(),
            'name' => 'Test client',
            'api_key' => ApiClient::hashKey($plain),
            'key_prefix' => explode('.', $plain, 2)[0],
            'status' => 'active',
        ]);

        $a = $this->postJson('/api/v1/documents/emit', $this->sampleEmitPayload($tenant, 'seq-a'), [
            'Authorization' => 'Bearer '.$plain,
        ])->assertCreated();
        $b = $this->postJson('/api/v1/documents/emit', $this->sampleEmitPayload($tenant, 'seq-b'), [
            'Authorization' => 'Bearer '.$plain,
        ])->assertCreated();

        $this->assertSame('00000001', $a->json('document_number'));
        $this->assertSame('00000002', $b->json('document_number'));
    }

    public function test_issued_document_cannot_be_updated_via_model_in_test_suite(): void
    {
        $tenant = Tenant::query()->create(['name' => 'Acme', 'slug' => 'acme-4']);
        [$plain] = ApiClient::generateKeyPair();
        ApiClient::query()->create([
            'tenant_id' => $tenant->getKey(),
            'name' => 'Test client',
            'api_key' => ApiClient::hashKey($plain),
            'key_prefix' => explode('.', $plain, 2)[0],
            'status' => 'active',
        ]);

        $this->postJson('/api/v1/documents/emit', $this->sampleEmitPayload($tenant, 'immut-1'), [
            'Authorization' => 'Bearer '.$plain,
        ])->assertCreated();

        $doc = FiscalDocument::query()->firstOrFail();
        $this->expectException(\LogicException::class);
        $doc->update(['subtotal' => '0.0000']);
    }

    public function test_audit_logs_are_written_on_emit(): void
    {
        $tenant = Tenant::query()->create(['name' => 'Acme', 'slug' => 'acme-5']);
        [$plain] = ApiClient::generateKeyPair();
        ApiClient::query()->create([
            'tenant_id' => $tenant->getKey(),
            'name' => 'Test client',
            'api_key' => ApiClient::hashKey($plain),
            'key_prefix' => explode('.', $plain, 2)[0],
            'status' => 'active',
        ]);

        $this->postJson('/api/v1/documents/emit', $this->sampleEmitPayload($tenant, 'audit-1'), [
            'Authorization' => 'Bearer '.$plain,
        ])->assertCreated();

        $this->assertGreaterThan(0, AuditLog::query()->where('action', 'documents.emit')->count());
    }

    public function test_sanctum_user_can_emit(): void
    {
        $tenant = Tenant::query()->create(['name' => 'Acme', 'slug' => 'acme-6']);
        $user = User::factory()->create(['tenant_id' => $tenant->getKey()]);
        $token = $user->createToken('test')->plainTextToken;

        $this->postJson('/api/v1/documents/emit', $this->sampleEmitPayload($tenant, 'user-1'), [
            'Authorization' => 'Bearer '.$token,
        ])->assertCreated();
    }

    public function test_get_document_returns_json(): void
    {
        $tenant = Tenant::query()->create(['name' => 'Acme', 'slug' => 'acme-7']);
        [$plain] = ApiClient::generateKeyPair();
        ApiClient::query()->create([
            'tenant_id' => $tenant->getKey(),
            'name' => 'Test client',
            'api_key' => ApiClient::hashKey($plain),
            'key_prefix' => explode('.', $plain, 2)[0],
            'status' => 'active',
        ]);

        $created = $this->postJson('/api/v1/documents/emit', $this->sampleEmitPayload($tenant, 'get-1'), [
            'Authorization' => 'Bearer '.$plain,
        ])->assertCreated();

        $id = $created->json('id');
        $this->getJson('/api/v1/documents/'.$id, [
            'Authorization' => 'Bearer '.$plain,
        ])->assertOk()->assertJsonPath('document_number', '00000001')
            ->assertJsonPath('customer.legal_name', 'Cliente Fiscal');
    }
}
