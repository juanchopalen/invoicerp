<?php

namespace App\DTOs;

use App\Models\Customer;

final readonly class EmitCustomerInput
{
    public function __construct(
        public ?int $customerId,
        public string $legalName,
        public string $taxId,
        public string $address,
        public string $municipality,
        public string $state,
        public ?string $city,
        public ?string $email,
        public ?string $phone,
        public string $country,
    ) {}

    public static function fromCustomer(Customer $customer): self
    {
        return new self(
            customerId: (int) $customer->getKey(),
            legalName: $customer->legal_name,
            taxId: $customer->tax_id,
            address: $customer->address,
            municipality: $customer->municipality,
            state: (string) $customer->state?->name,
            city: $customer->city?->name,
            email: $customer->email,
            phone: $customer->phone,
            country: $customer->country?->iso2 ?? 'VE',
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toDocumentAttributes(): array
    {
        return [
            'customer_id' => $this->customerId,
            'customer_legal_name' => $this->legalName,
            'customer_tax_id' => $this->taxId,
            'customer_email' => $this->email,
            'customer_phone' => $this->phone,
            'customer_address' => $this->address,
            'customer_city' => $this->city,
            'customer_municipality' => $this->municipality,
            'customer_state' => $this->state,
            'customer_country' => strtoupper($this->country),
        ];
    }

    /**
     * Normalized payload for idempotency hashing.
     *
     * @return array<string, mixed>
     */
    public function toIdempotencyPayload(): array
    {
        return [
            'customer_id' => $this->customerId,
            'legal_name' => $this->legalName,
            'tax_id' => $this->taxId,
            'address' => $this->address,
            'municipality' => $this->municipality,
            'state' => $this->state,
            'city' => $this->city,
            'email' => $this->email,
            'phone' => $this->phone,
            'country' => strtoupper($this->country),
        ];
    }
}
