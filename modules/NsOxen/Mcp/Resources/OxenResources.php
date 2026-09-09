<?php

namespace Modules\NsOxen\Mcp\Resources;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Resource;

abstract class OxenResource extends Resource
{
    protected string $permission;

    public function shouldRegister( Request $request ): bool
    {
        $user = $request->user();

        return $user instanceof User && $user->allowedTo( [ 'ns.oxen.use' ] ) && $user->allowedTo( [ $this->permission ] );
    }
}
class StoreConfigurationResource extends OxenResource
{
    protected string $uri = 'oxen://store/configuration';

    protected string $mimeType = 'application/json';

    protected string $permission = 'ns.oxen.use';

    protected string $description = 'Safe current store profile and formatting configuration.';

    public function handle(): Response
    {
        return Response::json( (array) ns()->option->get( ['ns_store_name', 'ns_currency_symbol', 'ns_currency_iso', 'ns_currency_precision', 'ns_timezone', 'ns_date_format', 'ns_time_format', 'ns_default_theme', 'ns_store_address', 'ns_store_phone', 'ns_store_email'] ) );
    }
}
class PaymentTypesResource extends OxenResource
{
    protected string $uri = 'oxen://store/payment-types';

    protected string $mimeType = 'application/json';

    protected string $permission = 'nexopos.read.orders';

    protected string $description = 'Configured payment types.';

    public function handle(): Response
    {
        return Response::json( DB::table( 'nexopos_payments_types' )->orderBy( 'label' )->limit( 100 )->get( ['id', 'identifier', 'label', 'active'] )->toArray() );
    }
}
class ProductCategoriesResource extends OxenResource
{
    protected string $uri = 'oxen://store/product-categories';

    protected string $mimeType = 'application/json';

    protected string $permission = 'nexopos.read.categories';

    protected string $description = 'Bounded product category reference data.';

    public function handle(): Response
    {
        return Response::json( DB::table( 'nexopos_products_categories' )->orderBy( 'name' )->limit( 500 )->get( ['id', 'name', 'parent_id'] )->toArray() );
    }
}
class TaxGroupsResource extends OxenResource
{
    protected string $uri = 'oxen://store/tax-groups';

    protected string $mimeType = 'application/json';

    protected string $permission = 'nexopos.read.taxes';

    protected string $description = 'Configured tax groups.';

    public function handle(): Response
    {
        return Response::json( DB::table( 'nexopos_taxes_groups' )->orderBy( 'name' )->limit( 100 )->get( ['id', 'name'] )->toArray() );
    }
}
