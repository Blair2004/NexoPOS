<?php

namespace Modules\NsOxen\Data;

use App\Models\User;
use Modules\NsOxen\Models\Conversation;

final readonly class OxenExecutionContext
{
    /** @param list<string> $abilities */
    public function __construct(
        public User $actor,
        public array $abilities,
        public ?int $storeId,
        public ?Conversation $conversation,
        public string $correlationId,
        public ?string $idempotencyKey = null,
    ) {}
}
