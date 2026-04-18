<?php

namespace App\DTOs;

final readonly class CancelDocumentInput
{
    public function __construct(
        public ?int $documentId,
        public ?string $sourceSystem,
        public ?string $externalReference,
        public string $reason,
    ) {}
}
