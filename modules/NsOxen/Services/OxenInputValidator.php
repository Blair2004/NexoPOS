<?php

namespace Modules\NsOxen\Services;

final class OxenInputValidator
{
    /** @param array<string, mixed> $schema */
    public static function ensureShape( mixed $value, array $schema, string $path = 'input' ): void
    {
        $types = (array) ( $schema['type'] ?? [] );
        if ( in_array( 'object', $types, true ) && is_array( $value ) ) {
            $properties = (array) ( $schema['properties'] ?? [] );
            if ( ( $schema['additionalProperties'] ?? true ) === false ) {
                $unknown = array_diff( array_keys( $value ), array_keys( $properties ) );
                if ( $unknown !== [] ) {
                    throw new OxenException( 'VALIDATION_FAILED', sprintf(
                        __m( 'Unsupported field at %1$s: %2$s.', 'NsOxen' ),
                        $path,
                        (string) reset( $unknown ),
                    ) );
                }
            }

            foreach ( $value as $key => $child ) {
                if ( isset( $properties[$key] ) && is_array( $properties[$key] ) ) {
                    self::ensureShape( $child, $properties[$key], $path . '.' . $key );
                } elseif ( is_array( $schema['additionalProperties'] ?? null ) ) {
                    self::ensureShape( $child, $schema['additionalProperties'], $path . '.' . $key );
                }
            }
        }

        if ( in_array( 'array', $types, true ) && is_array( $value ) && is_array( $schema['items'] ?? null ) ) {
            foreach ( $value as $index => $child ) {
                self::ensureShape( $child, $schema['items'], $path . '.' . $index );
            }
        }
    }
}
