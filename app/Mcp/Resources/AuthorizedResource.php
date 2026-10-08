<?php

namespace App\Mcp\Resources;

use Laravel\Mcp\Request;
use Laravel\Mcp\Server\Resource;

abstract class AuthorizedResource extends Resource
{
    /**
     * @var array<int, string>
     */
    protected array $permissions = [];

    public function shouldRegister( Request $request ): bool
    {
        $user = $request->user();

        return $user !== null &&
            $this->permissions !== [] &&
            $user->allowedTo( $this->permissions );
    }
}
