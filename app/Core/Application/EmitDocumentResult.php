<?php

namespace App\Core\Application;

final readonly class EmitDocumentResult
{
    public function __construct(
        public int $httpStatus,
        public array $data,
        public bool $idempotentReplay,
    ) {}
}
