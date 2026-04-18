<?php

namespace App\DTOs;

final readonly class EmitDocumentInput
{
    /**
     * @param  array<int, EmitDocumentLineInput>  $lines
     */
    public function __construct(
        public string $sourceSystem,
        public string $externalReference,
        public string $documentType,
        public string $currency,
        public int $schemaVersion,
        public EmitCustomerInput $customer,
        /** @var array<int, EmitDocumentLineInput> */
        public array $lines,
    ) {}

    public function idempotencyHash(): string
    {
        $linePayloads = [];
        foreach ($this->lines as $line) {
            $linePayloads[] = [
                'line_number' => $line->lineNumber,
                'description' => $line->description,
                'qty' => $line->qty,
                'unit_price' => $line->unitPrice,
                'tax_rate' => $line->taxRate,
                'line_subtotal' => $line->lineSubtotal,
                'line_tax' => $line->lineTax,
                'line_total' => $line->lineTotal,
                'totals' => $line->totals,
                'product_id' => $line->productId,
            ];
        }
        usort($linePayloads, fn (array $a, array $b): int => $a['line_number'] <=> $b['line_number']);

        $payload = [
            'source_system' => $this->sourceSystem,
            'external_reference' => $this->externalReference,
            'document_type' => $this->documentType,
            'currency' => $this->currency,
            'schema_version' => $this->schemaVersion,
            'customer' => $this->customer->toIdempotencyPayload(),
            'lines' => $linePayloads,
        ];

        return hash('sha256', json_encode($payload, JSON_THROW_ON_ERROR));
    }
}
