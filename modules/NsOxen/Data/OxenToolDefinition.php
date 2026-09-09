<?php

namespace Modules\NsOxen\Data;

use InvalidArgumentException;

final readonly class OxenToolDefinition
{
    /**
     * @param array<string, mixed> $inputSchema
     * @param array<string, mixed> $outputSchema
     * @param array<string, mixed> $rules
     * @param list<string> $permissions
     */
    public function __construct(
        public string $name,
        public string $title,
        public string $description,
        public string $category,
        public string $sourceModule,
        public array $inputSchema,
        public array $outputSchema,
        public array $rules,
        public string $ability,
        public array $permissions,
        public string $risk = 'read',
        public bool $requiresConfirmation = false,
    ) {
        if ( preg_match( '/^[a-z][a-z0-9_]*$/', $name ) !== 1 ) {
            throw new InvalidArgumentException( "Invalid Oxen tool name [{$name}]." );
        }

        if ( ! in_array( $risk, ['read', 'write', 'destructive'], true ) ) {
            throw new InvalidArgumentException( "Invalid Oxen risk [{$risk}]." );
        }

        if ( ( $inputSchema['type'] ?? null ) !== 'object' || ( $outputSchema['type'] ?? null ) !== 'object' ) {
            throw new InvalidArgumentException( "Oxen tool [{$name}] must use object input and output schemas." );
        }
    }

    public function isWrite(): bool
    {
        return $this->risk !== 'read';
    }

    /** @return array<string, mixed> */
    public function forCatalog(): array
    {
        return [
            'name' => $this->name,
            'title' => $this->title,
            'description' => strip_tags( $this->description ),
            'category' => $this->category,
            'source_module' => $this->sourceModule,
            'risk' => $this->risk,
            'requires_confirmation' => $this->requiresConfirmation,
        ];
    }

    /** @return array<string, mixed> */
    public function forOpenAI(): array
    {
        $parameters = $this->inputSchema;

        if ( ( $parameters['properties'] ?? null ) === [] ) {
            $parameters['properties'] = (object) [];
        }

        if ( $this->isWrite() ) {
            unset( $parameters['properties']['idempotency_key'] );
            $parameters['required'] = array_values( array_filter(
                $parameters['required'] ?? [],
                static fn ( string $field ): bool => $field !== 'idempotency_key',
            ) );
        }

        return [
            'type' => 'function',
            'name' => $this->name,
            'description' => strip_tags( $this->description ),
            'parameters' => $parameters,
            'strict' => false,
        ];
    }
}
