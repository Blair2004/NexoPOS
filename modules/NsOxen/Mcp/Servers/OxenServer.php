<?php

namespace Modules\NsOxen\Mcp\Servers;

require_once __DIR__ . '/../Resources/OxenResources.php';

use Laravel\Mcp\Server;
use Laravel\Mcp\Server\Attributes\Instructions;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Version;
use Modules\NsOxen\Mcp\Resources\PaymentTypesResource;
use Modules\NsOxen\Mcp\Resources\ProductCategoriesResource;
use Modules\NsOxen\Mcp\Resources\StoreConfigurationResource;
use Modules\NsOxen\Mcp\Resources\TaxGroupsResource;
use Modules\NsOxen\Mcp\Tools\OxenTool;
use Modules\NsOxen\Services\ToolRegistry;

#[Name( 'Oxen' )]
#[Version( '2.0.0' )]
#[Instructions( 'Oxen provides permission-scoped NexoPOS tools from the same registry used by the embedded assistant. Store context is trusted server context and must never be supplied as tool input.' )]
class OxenServer extends Server
{
    protected array $resources = [StoreConfigurationResource::class, PaymentTypesResource::class, ProductCategoriesResource::class, TaxGroupsResource::class];

    protected function boot(): void
    {
        $this->tools = collect( app( ToolRegistry::class )->definitions() )
            ->map( fn ( $definition ): OxenTool => new OxenTool( $definition ) )
            ->values()
            ->all();
    }
}
