<?php

namespace App\Mcp\Tools;

use Laravel\Mcp\Request;
use Laravel\Mcp\Server\Tool;

abstract class AuthorizedTool extends Tool
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
