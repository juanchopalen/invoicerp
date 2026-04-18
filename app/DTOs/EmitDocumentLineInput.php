<?php

namespace App\DTOs;

final readonly class EmitDocumentLineInput
{
    public function __construct(
        public int $lineNumber,
        public string $description,
        public string $qty,
        public string $unitPrice,
        public string $taxRate,
        public string $lineSubtotal,
        public string $lineTax,
        public string $lineTotal,
        /** @var array<string, mixed>|null */
        public ?array $totals,
        public ?int $productId = null,
    ) {}
}
