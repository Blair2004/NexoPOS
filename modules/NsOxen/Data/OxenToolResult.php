<?php

namespace Modules\NsOxen\Data;

final readonly class OxenToolResult
{
    /** @param array<string, mixed>|list<mixed> $data */
    public function __construct(
        public array $data,
        public ?string $reference = null,
    ) {}

    /** @return array{ok: true, data: array<string, mixed>|list<mixed>, reference: ?string} */
    public function toArray(): array
    {
        return ['ok' => true, 'data' => $this->data, 'reference' => $this->reference];
    }
}
