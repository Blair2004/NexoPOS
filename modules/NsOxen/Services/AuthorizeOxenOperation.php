<?php

namespace Modules\NsOxen\Services;

use App\Models\User;
use Modules\NsOxen\Models\Setting;

class AuthorizeOxenOperation
{
    /** @param string|list<string> $permissions */
    public function allows( User $user, string $ability, string|array $permissions, bool $write = false, bool $destructive = false ): bool
    {
        $token = $user->currentAccessToken();
        if ( $token && ! $token->can( $ability ) ) {
            return false;
        }
        if ( ! $user->allowedTo( [ 'ns.oxen.use' ] ) || ! $user->allowedTo( (array) $permissions ) ) {
            return false;
        }
        if ( $write && ( ! Setting::query()->value( 'writes_enabled' ) || ! $user->allowedTo( [ 'ns.oxen.use-writes' ] ) ) ) {
            return false;
        }

        return ! $destructive || ( $user->allowedTo( [ 'ns.oxen.use-destructive' ] ) && ( $token === null || $token->can( 'oxen:destructive' ) ) );
    }

    /** @param string|list<string> $permissions */
    public function ensure( User $user, string $ability, string|array $permissions, bool $write = false, bool $destructive = false ): void
    {
        if ( ! $this->allows( $user, $ability, $permissions, $write, $destructive ) ) {
            throw new OxenException( 'FORBIDDEN', __m( 'You are not allowed to perform this Oxen operation.', 'NsOxen' ), 403 );
        }
    }
}
