<?php

declare(strict_types=1);

namespace App\Mcp\Resources;

use App\Models\TaxGroup;
use Laravel\Mcp\Response;

class TaxGroupsResource extends AuthorizedResource
{
    protected array $permissions = [ 'nexopos.read.taxes' ];

    protected string $uri = 'pos://resources/tax-groups';

    protected string $mimeType = 'application/json';

    protected string $description = 'All tax groups with their associated tax rates configured in the POS system.';

    public function handle(): Response
    {
        try {
            $groups = TaxGroup::with( 'taxes' )->get();

            return Response::json( $groups->toArray() );
        } catch ( \Throwable $e ) {
            return Response::error( $e->getMessage() );
        }
    }
}
