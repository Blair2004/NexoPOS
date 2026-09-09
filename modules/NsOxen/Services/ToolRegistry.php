<?php

namespace Modules\NsOxen\Services;

use App\Models\Customer;
use App\Models\Order;
use App\Models\User;
use App\Services\ReportService;
use Carbon\Carbon;
use Illuminate\Contracts\Container\Container;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Modules\NsOxen\Contracts\OxenToolContract;
use Modules\NsOxen\Data\OxenExecutionContext;
use Modules\NsOxen\Data\OxenToolDefinition;
use Modules\NsOxen\Models\IdempotencyRecord;
use Modules\NsOxen\Models\Operation;
use Modules\NsOxen\Models\Conversation;
use Modules\NsOxen\Models\Product;
use Modules\NsOxen\Models\ProductCategory;
use Throwable;

class ToolRegistry
{
    /** @var array<class-string<OxenToolContract>, true> */
    private array $registered = [];

    public function __construct(
        private readonly AuthorizeOxenOperation $authorization,
        private readonly SafeWriteTools $safeWrites,
        private readonly ProductManagementService $productManagement,
        private readonly CatalogCreationService $catalogCreation,
        private readonly ReportService $reportService,
        private readonly Container $container,
        private readonly ?ConfigurationManagementService $configurationManagement = null,
    ) {}

    /** @param list<class-string<OxenToolContract>> $tools */
    public function registerTools( array $tools ): void
    {
        foreach ( $tools as $tool ) {
            try {
                if ( ! is_subclass_of( $tool, OxenToolContract::class ) ) {
                    throw new InvalidArgumentException( "Oxen tool [{$tool}] must implement OxenToolContract." );
                }

                $definition = $this->container->make( $tool )->definition();
                if ( $this->definition( $definition->name ) !== null ) {
                    throw new InvalidArgumentException( "Duplicate Oxen tool name [{$definition->name}]." );
                }

                $this->registered[$tool] = true;
            } catch ( \Throwable $exception ) {
                Log::error( 'Oxen tool registration rejected.', ['tool' => $tool, 'exception' => $exception::class, 'message' => $exception->getMessage()] );
                throw $exception;
            }
        }
    }

    public function names(): array
    {
        return array_keys( $this->definitions() );
    }

    /** @return array<string, OxenToolDefinition> */
    public function definitions(): array
    {
        $definitions = $this->coreDefinitions();
        foreach ( array_keys( $this->registered ) as $tool ) {
            $definition = $this->container->make( $tool )->definition();
            if ( isset( $definitions[$definition->name] ) ) {
                throw new InvalidArgumentException( "Duplicate Oxen tool name [{$definition->name}]." );
            }
            $definitions[$definition->name] = $definition;
        }

        return $definitions;
    }

    public function definition( string $name ): ?OxenToolDefinition
    {
        return $this->coreDefinitions()[$name] ?? collect( array_keys( $this->registered ) )
            ->map( fn ( string $tool ): OxenToolDefinition => $this->container->make( $tool )->definition() )
            ->first( fn ( OxenToolDefinition $definition ): bool => $definition->name === $name );
    }

    /** @return array<string, OxenToolDefinition> */
    public function availableDefinitions( User $user ): array
    {
        return array_filter( $this->definitions(), fn ( OxenToolDefinition $definition ): bool => $this->available( $user, $definition->name ) );
    }

    public function available( User $user, string $name ): bool
    {
        $definition = $this->definition( $name );

        return $definition !== null && $this->authorization->allows(
            $user,
            $definition->ability,
            $definition->permissions,
            $definition->isWrite(),
            $definition->risk === 'destructive',
        );
    }

    public function execute( User $user, string $name, array $input, ?Conversation $conversation = null ): array
    {
        $definition = $this->definition( $name ) ?? throw new OxenException( 'NOT_FOUND', __m( 'Unknown Oxen tool.', 'NsOxen' ), 404 );
        if ( array_key_exists( 'store_id', $input ) ) {
            throw new OxenException( 'VALIDATION_FAILED', __m( 'Store context cannot be supplied as tool input.', 'NsOxen' ) );
        }
        OxenInputValidator::ensureShape( $input, $definition->inputSchema );
        $this->authorization->ensure( $user, $definition->ability, $definition->permissions, $definition->isWrite(), $definition->risk === 'destructive' );
        $validated = Validator::make( $input, $definition->rules )->validate();
        $this->preflightProposal( $name, $validated );
        $started = microtime( true );
        $context = $this->context( $user, $validated, $conversation );
        $hash = hash( 'sha256', json_encode( ['store_id' => $context->storeId, 'input' => $validated], JSON_THROW_ON_ERROR ) );
        try {
            $externalTool = $this->registeredTool( $name );
            $result = $externalTool
                ? $externalTool->execute( $context, $validated )->data
                : ( $definition->isWrite()
                    ? ( in_array( $name, ['generate_product_image', 'generate_store_logo'], true ) ? $this->writeProcessingTool( $user, $name, $validated, $hash ) : $this->write( $user, $name, $validated, $hash ) )
                    : $this->read( $name, $validated ) );
            $status = 'completed';

            return ['ok' => true, 'data' => $result];
        } catch ( Throwable $exception ) {
            $status = 'failed';
            throw $exception;
        } finally {
            Operation::query()->create( ['correlation_id' => $context->correlationId, 'user_id' => $user->id, 'token_id' => $user->currentAccessToken()?->id, 'store_id' => $context->storeId, 'tool' => $name, 'redacted_input' => $this->redact( $validated ), 'input_hash' => $hash, 'status' => $status, 'duration_ms' => (int) ( ( microtime( true ) - $started ) * 1000 )] );
        }
    }

    /** @param array<string, mixed> $input */
    public function preflightProposal( string $name, array $input ): void
    {
        if ( $name === 'import_products' ) {
            $this->catalogCreation->preflightImport( $input );
        }
        if ( in_array( $name, [
            'update_unit_group', 'update_unit', 'update_tax_group', 'update_tax',
            'update_coupon', 'update_customer_group',
        ], true ) ) {
            $definition = $this->definition( $name );
            $mutable = array_diff( array_keys( $definition?->inputSchema['properties'] ?? [] ), ['id', 'idempotency_key'] );
            if ( array_intersect( array_keys( $input ), $mutable ) === [] ) {
                throw new OxenException( 'VALIDATION_FAILED', __m( 'At least one field must be supplied for an update.', 'NsOxen' ) );
            }
        }
    }

    private function registeredTool( string $name ): ?OxenToolContract
    {
        foreach ( array_keys( $this->registered ) as $tool ) {
            $instance = $this->container->make( $tool );
            if ( $instance->definition()->name === $name ) {
                return $instance;
            }
        }

        return null;
    }

    private function configuration(): ConfigurationManagementService
    {
        return $this->configurationManagement ?? $this->container->make( ConfigurationManagementService::class );
    }

    /** @param array<string, mixed> $input */
    private function context( User $user, array $input, ?Conversation $conversation ): OxenExecutionContext
    {
        $token = $user->currentAccessToken();
        $store = isset( ns()->store ) && method_exists( ns()->store, 'getCurrentStore' ) ? ns()->store->getCurrentStore() : null;

        return new OxenExecutionContext(
            actor: $user,
            abilities: $token ? $token->abilities : [],
            storeId: $store?->id,
            conversation: $conversation,
            correlationId: (string) Str::uuid(),
            idempotencyKey: isset( $input['idempotency_key'] ) ? (string) $input['idempotency_key'] : null,
        );
    }

    private function read( string $name, array $input ): array
    {
        $limit = max( 1, min( 50, (int) ( $input['limit'] ?? 10 ) ) );
        $search = trim( (string) ( $input['search'] ?? '' ) );
        $reference = $this->referenceTool( $name, $input );
        if ( $reference !== null ) {
            return $reference;
        }

        return match ( $name ) {
            'get_inventory_quantities' => $this->inventoryQuantities( $input, $limit ),
            'get_product_sales_performance' => $this->getProductSalesPerformance( $input, $limit ),
            'search_products' => Product::query()->with( 'category:id,name' )->when( $search, fn ( $query ) => $query->where( fn ( $query ) => $query->where( 'name', 'like', '%' . $search . '%' )->orWhere( 'sku', 'like', '%' . $search . '%' )->orWhere( 'barcode', 'like', '%' . $search . '%' ) ) )->latest( 'id' )->limit( $limit )->get( ['id', 'name', 'sku', 'barcode', 'category_id', 'status'] )->toArray(),
            'get_product' => ( Product::query()->with( 'category:id,name' )->find( (int) ( $input['id'] ?? 0 ) )?->only( ['id', 'name', 'sku', 'barcode', 'category_id', 'status', 'description'] ) ?? throw new OxenException( 'NOT_FOUND', __m( 'Product not found.', 'NsOxen' ), 404 ) ),
            'get_low_stock_products' => DB::table( 'nexopos_products_unit_quantities' )->whereColumn( 'quantity', '<=', 'low_quantity' )->orderBy( 'quantity' )->limit( $limit )->get()->toArray(),
            'search_customers' => Customer::query()->when( $search, fn( $q ) => $q->where( fn( $q ) => $q->where( 'first_name', 'like', '%' . $search . '%' )->orWhere( 'last_name', 'like', '%' . $search . '%' )->orWhere( 'email', 'like', '%' . $search . '%' ) ) )->latest( 'id' )->limit( $limit )->get( ['id', 'first_name', 'last_name', 'email', 'phone', 'account_amount'] )->toArray(),
            'get_customer' => ( Customer::query()->find( (int) ( $input['id'] ?? 0 ) )?->only( ['id', 'first_name', 'last_name', 'email', 'phone', 'account_amount', 'purchases_amount'] ) ?? throw new OxenException( 'NOT_FOUND', __m( 'Customer not found.', 'NsOxen' ), 404 ) ),
            'search_orders' => Order::query()
                ->when( $search, fn ( $q ) => $q->where( 'code', 'like', '%' . $search . '%' ) )
                ->when( $input['date_start'] ?? null, fn ( $q, $date ) => $q->where( 'created_at', '>=', Carbon::parse( $date )->startOfDay() ) )
                ->when( $input['date_end'] ?? null, fn ( $q, $date ) => $q->where( 'created_at', '<=', Carbon::parse( $date )->endOfDay() ) )
                ->when( $input['payment_status'] ?? null, fn ( $q, $status ) => $q->where( 'payment_status', $status ) )
                ->latest( 'id' )->limit( $limit )->get( ['id', 'code', 'customer_id', 'payment_status', 'total', 'created_at'] )->toArray(),
            'get_order' => ( Order::query()->with( ['products:id,order_id,product_id,name,quantity,total_price'] )->find( (int) ( $input['id'] ?? 0 ) )?->toArray() ?? throw new OxenException( 'NOT_FOUND', __m( 'Order not found.', 'NsOxen' ), 404 ) ),
            'search_product_sales' => DB::table( 'nexopos_orders_products' )->selectRaw( 'product_id, name, SUM(quantity) as quantity, SUM(total_price) as total' )->groupBy( 'product_id', 'name' )->orderByDesc( 'total' )->limit( $limit )->get()->toArray(),
            'search_wallet_history' => DB::table( 'nexopos_customers_account_history' )->when( isset( $input['customer_id'] ), fn( $q ) => $q->where( 'customer_id', (int) $input['customer_id'] ) )->latest( 'id' )->limit( $limit )->get()->toArray(),
            'get_dashboard_summary' => $this->dashboardSummary( $input ),
            'get_payment_method_totals' => DB::table( 'nexopos_orders_payments' )->selectRaw( 'identifier, COUNT(*) as payments, SUM(value) as total' )->groupBy( 'identifier' )->orderByDesc( 'total' )->limit( $limit )->get()->toArray(),
            'get_cashier_ranking' => DB::table( 'nexopos_orders' )->selectRaw( 'author_id, COUNT(*) as orders, SUM(total) as total' )->when( $input['date_start'] ?? null, fn ( $query, $date ) => $query->where( 'created_at', '>=', Carbon::parse( $date )->startOfDay() ) )->when( $input['date_end'] ?? null, fn ( $query, $date ) => $query->where( 'created_at', '<=', Carbon::parse( $date )->endOfDay() ) )->groupBy( 'author_id' )->orderByDesc( 'total' )->limit( $limit )->get()->toArray(),
            'get_refund_summary' => ['refunds' => DB::table( 'nexopos_orders_refunds' )->when( $input['date_start'] ?? null, fn ( $query, $date ) => $query->where( 'created_at', '>=', Carbon::parse( $date )->startOfDay() ) )->when( $input['date_end'] ?? null, fn ( $query, $date ) => $query->where( 'created_at', '<=', Carbon::parse( $date )->endOfDay() ) )->count(), 'total' => (float) DB::table( 'nexopos_orders_refunds' )->when( $input['date_start'] ?? null, fn ( $query, $date ) => $query->where( 'created_at', '>=', Carbon::parse( $date )->startOfDay() ) )->when( $input['date_end'] ?? null, fn ( $query, $date ) => $query->where( 'created_at', '<=', Carbon::parse( $date )->endOfDay() ) )->sum( 'total' )],
            'get_yearly_sales' => DB::table( 'nexopos_orders' )->selectRaw( 'MONTH(created_at) as month, COUNT(*) as orders, SUM(total) as total, SUM(tax_value) as taxes' )->whereYear( 'created_at', (int) ( $input['year'] ?? now()->year ) )->groupByRaw( 'MONTH(created_at)' )->orderBy( 'month' )->get()->toArray(),
            'compare_sales_periods' => ['current' => $this->dashboardSummary( ['date_start' => $input['current_start'], 'date_end' => $input['current_end']] ), 'previous' => $this->dashboardSummary( ['date_start' => $input['previous_start'], 'date_end' => $input['previous_end']] )],
            'get_profit_summary' => (array) DB::table( 'nexopos_orders_products' )->when( $input['date_start'] ?? null, fn ( $query, $date ) => $query->where( 'created_at', '>=', Carbon::parse( $date )->startOfDay() ) )->when( $input['date_end'] ?? null, fn ( $query, $date ) => $query->where( 'created_at', '<=', Carbon::parse( $date )->endOfDay() ) )->selectRaw( 'COALESCE(SUM(total_price), 0) as sales, COALESCE(SUM(total_purchase_price), 0) as cost, COALESCE(SUM(total_price - total_purchase_price), 0) as gross_profit' )->first(),
            'get_coupon' => $this->configuration()->getCoupon( (int) $input['id'] ),
            'search_tax_groups' => $this->configuration()->searchTaxGroups( $search, $limit ),
            'get_store_settings' => (array) ns()->option->get( ['ns_store_name', 'ns_currency_symbol', 'ns_currency_iso', 'ns_currency_precision', 'ns_date_format', 'ns_time_format', 'ns_timezone', 'ns_default_theme', 'ns_store_address', 'ns_store_phone', 'ns_store_email', 'ns_store_square_logo', 'ns_store_rectangle_logo', 'ns_invoice_receipt_logo'] ),
            default => throw new OxenException( 'NOT_FOUND', __m( 'Unknown Oxen read tool.', 'NsOxen' ), 404 ),
        };
    }

    /** @return array<int, object>|null */
    private function referenceTool( string $name, array $input ): ?array
    {
        $references = [
            'search_stock_history' => ['nexopos_products_histories', ['id', 'product_id', 'operation_type', 'unit_id', 'before_quantity', 'quantity', 'after_quantity', 'total_price', 'created_at'], []],
            'search_categories' => ['nexopos_products_categories', ['id', 'name', 'parent_id', 'description'], ['name']],
            'search_units' => ['nexopos_units', ['id', 'name', 'identifier', 'group_id', 'value', 'base_unit'], ['name', 'identifier']],
            'search_unit_groups' => ['nexopos_units_groups', ['id', 'name', 'description'], ['name']],
            'search_customer_groups' => ['nexopos_customers_groups', ['id', 'name', 'description', 'reward_system_id'], ['name']],
            'search_rewards' => ['nexopos_rewards_system', ['id', 'name', 'target', 'description', 'coupon_id'], ['name']],
            'search_coupons' => ['nexopos_coupons', ['id', 'name', 'code', 'type', 'discount_value', 'valid_until'], ['name', 'code']],
            'search_providers' => ['nexopos_providers', ['id', 'first_name', 'last_name', 'email', 'phone', 'amount_due', 'amount_paid'], ['first_name', 'last_name', 'email']],
            'search_procurements' => ['nexopos_procurements', ['id', 'name', 'provider_id', 'value', 'cost', 'payment_status', 'delivery_status', 'total_items', 'created_at'], ['name', 'invoice_reference']],
            'search_registers' => ['nexopos_registers', ['id', 'name', 'status', 'used_by', 'balance'], ['name']],
            'search_register_history' => ['nexopos_registers_history', ['id', 'register_id', 'order_id', 'action', 'value', 'balance_before', 'balance_after', 'created_at'], []],
            'search_taxes' => ['nexopos_taxes', ['id', 'name', 'rate', 'tax_group_id', 'description'], ['name']],
            'search_media' => ['nexopos_medias', ['id', 'name', 'extension', 'slug', 'created_at'], ['name', 'slug']],
            'search_installments' => ['nexopos_orders_instalments', ['id', 'order_id', 'amount', 'paid', 'payment_id', 'date'], []],
            'search_transactions' => ['nexopos_transactions', ['id', 'name', 'account_id', 'value', 'type', 'active', 'scheduled_date', 'created_at'], ['name']],
            'search_accounts' => ['nexopos_transactions_accounts', ['id', 'name', 'account', 'sub_category_id', 'category_identifier', 'description'], ['name', 'category_identifier']],
        ];
        if ( ! isset( $references[$name] ) ) {
            return null;
        }

        [$table, $columns, $searchable] = $references[$name];
        $limit = max( 1, min( 50, (int) ( $input['limit'] ?? 10 ) ) );
        $search = trim( (string) ( $input['search'] ?? '' ) );

        return DB::table( $table )
            ->when( $search !== '' && $searchable !== [], function ( $query ) use ( $search, $searchable ): void {
                $query->where( function ( $query ) use ( $search, $searchable ): void {
                    foreach ( $searchable as $index => $column ) {
                        $index === 0 ? $query->where( $column, 'like', '%' . $search . '%' ) : $query->orWhere( $column, 'like', '%' . $search . '%' );
                    }
                } );
            } )
            ->latest( 'id' )
            ->limit( $limit )
            ->get( $columns )
            ->toArray();
    }

    private function dashboardSummary( array $input ): array
    {
        $dateStart = isset( $input['date_start'] ) ? Carbon::parse( $input['date_start'] )->startOfDay()->toDateTimeString() : null;
        $dateEnd = isset( $input['date_end'] ) ? Carbon::parse( $input['date_end'] )->endOfDay()->toDateTimeString() : null;
        $report = $this->reportService->computeDayReport( $dateStart, $dateEnd );

        return [
            'date_start' => $report->range_starts,
            'date_end' => $report->range_ends,
            'paid_sales' => (float) ( $report->day_paid_orders ?? 0 ),
            'paid_orders' => (int) ( $report->day_paid_orders_count ?? 0 ),
            'unpaid_sales' => (float) ( $report->day_unpaid_orders ?? 0 ),
            'unpaid_orders' => (int) ( $report->day_unpaid_orders_count ?? 0 ),
            'partially_paid_sales' => (float) ( $report->day_partially_paid_orders ?? 0 ),
            'partially_paid_orders' => (int) ( $report->day_partially_paid_orders_count ?? 0 ),
            'taxes' => (float) ( $report->day_taxes ?? 0 ),
            'income' => (float) ( $report->day_income ?? 0 ),
        ];
    }

    /** @return array<int, object> */
    private function inventoryQuantities( array $input, int $limit ): array
    {
        $search = trim( (string) ( $input['search'] ?? '' ) );

        return DB::table( 'nexopos_products_unit_quantities as quantities' )
            ->join( 'nexopos_products as products', 'products.id', '=', 'quantities.product_id' )
            ->join( 'nexopos_units as units', 'units.id', '=', 'quantities.unit_id' )
            ->when( $search !== '', fn ( $query ) => $query->where( 'products.name', 'like', '%' . $search . '%' ) )
            ->when( $input['product_ids'] ?? null, fn ( $query, array $productIds ) => $query->whereIn( 'quantities.product_id', $productIds ) )
            ->when( $input['unit_id'] ?? null, fn ( $query, int $unitId ) => $query->where( 'quantities.unit_id', $unitId ) )
            ->orderByDesc( 'quantities.id' )
            ->limit( $limit )
            ->get( [
                'quantities.id', 'quantities.product_id', 'products.name as product_name',
                'quantities.unit_id', 'units.name as unit_name', 'units.identifier as unit_identifier',
                'quantities.barcode', 'quantities.quantity', 'quantities.low_quantity',
                'quantities.sale_price', 'quantities.sale_price_edit', 'quantities.sale_price_net',
                'quantities.sale_price_gross', 'quantities.sale_price_tax', 'quantities.wholesale_price',
                'quantities.wholesale_price_edit', 'quantities.wholesale_price_net',
                'quantities.wholesale_price_gross', 'quantities.wholesale_price_tax', 'quantities.cogs',
                'quantities.is_weighable', 'quantities.scale_plu', 'quantities.stock_alert_enabled',
                'quantities.visible', 'quantities.expiration_date',
            ] )
            ->toArray();
    }

    /** @return array<string, mixed> */
    public function getProductSalesPerformance( array $input, int $limit = 50 ): array
    {
        $dateStart = Carbon::parse( $input['date_start'] )->startOfDay();
        $dateEnd = Carbon::parse( $input['date_end'] )->endOfDay();
        $tablePrefix = DB::connection()->getTablePrefix();
        $orderProductsAlias = $tablePrefix . 'order_products';
        $salesAlias = $tablePrefix . 'sales';
        $sales = DB::table( 'nexopos_orders_products as order_products' )
            ->join( 'nexopos_orders as orders', 'orders.id', '=', 'order_products.order_id' )
            ->whereBetween( 'orders.created_at', [$dateStart, $dateEnd] )
            ->where( 'orders.payment_status', '<>', Order::PAYMENT_VOID )
            ->groupBy( 'order_products.product_id' )
            ->selectRaw( "{$orderProductsAlias}.product_id, COALESCE(SUM({$orderProductsAlias}.quantity), 0) as quantity_sold, COALESCE(SUM({$orderProductsAlias}.total_price), 0) as sales_total" );

        $query = DB::table( 'nexopos_products as products' )
            ->leftJoinSub( $sales, 'sales', 'sales.product_id', '=', 'products.id' )
            ->leftJoin( 'nexopos_products_categories as categories', 'categories.id', '=', 'products.category_id' )
            ->when( isset( $input['category_id'] ), fn ( $query ) => $query->where( 'products.category_id', (int) $input['category_id'] ) )
            ->when( filled( $input['status'] ?? null ), fn ( $query ) => $query->where( 'products.status', $input['status'] ) )
            ->when( array_key_exists( 'pinned', $input ), fn ( $query ) => $query->where( 'products.pinned', (bool) $input['pinned'] ) )
            ->when( ! (bool) ( $input['include_zero_sales'] ?? false ), fn ( $query ) => $query->whereNotNull( 'sales.product_id' ) )
            ->when( isset( $input['max_quantity'] ), fn ( $query ) => $query->whereRaw( "COALESCE({$salesAlias}.quantity_sold, 0) <= ?", [(float) $input['max_quantity']] ) )
            ->when( isset( $input['max_revenue'] ), fn ( $query ) => $query->whereRaw( "COALESCE({$salesAlias}.sales_total, 0) <= ?", [(float) $input['max_revenue']] ) );

        $page = max( 1, (int) ( $input['page'] ?? 1 ) );
        $rows = $query
            ->orderByRaw( "COALESCE({$salesAlias}.quantity_sold, 0) ASC" )
            ->orderByRaw( "COALESCE({$salesAlias}.sales_total, 0) ASC" )
            ->orderBy( 'products.id' )
            ->offset( ( $page - 1 ) * $limit )
            ->limit( $limit )
            ->get( [
                'products.id as product_id',
                'products.name',
                'products.category_id',
                'categories.name as category_name',
                'products.status',
                'products.pinned',
                DB::raw( "COALESCE({$salesAlias}.quantity_sold, 0) as quantity_sold" ),
                DB::raw( "COALESCE({$salesAlias}.sales_total, 0) as sales_total" ),
            ] )
            ->toArray();

        $maximumPinned = (int) ns()->option->get( 'ns_pos_max_pinned_products', 5 );
        $currentPinned = Product::query()->where( 'pinned', true )->count();

        return [
            'date_start' => $dateStart->toDateString(),
            'date_end' => $dateEnd->toDateString(),
            'page' => $page,
            'limit' => $limit,
            'products' => $rows,
            'pin_capacity' => [
                'maximum' => $maximumPinned,
                'currently_pinned' => $currentPinned,
                'remaining' => max( 0, $maximumPinned - $currentPinned ),
            ],
        ];
    }

    private function write( User $user, string $name, array $input, string $hash ): array
    {
        $key = (string) ( $input['idempotency_key'] ?? '' );
        if ( $key === '' ) {
            throw new OxenException( 'VALIDATION_FAILED', __m( 'An idempotency key is required.', 'NsOxen' ) );
        }
        return DB::transaction( function () use ( $user, $name, $input, $key, $hash ): array {
            $existing = IdempotencyRecord::query()->where( ['user_id' => $user->id, 'tool' => $name, 'idempotency_key' => $key] )->lockForUpdate()->first();
            if ( $existing && $existing->expires_at->isFuture() ) {
                if ( $existing->input_hash !== $hash ) {
                    throw new OxenException( 'CONFLICT', __m( 'The idempotency key was already used with different input.', 'NsOxen' ), 409 );
                }

                return $existing->result;
            }
            $existing?->delete();
            $result = null;
            try {
                $result = match ( $name ) {
                    'create_product_category' => $this->saveCategory( $user, null, $input ), 'update_product_category' => $this->saveCategory( $user, (int) ( $input['id'] ?? 0 ), $input ),
                    'update_product' => $this->updateProduct( $input ), 'update_settings' => $this->updateSettings( $input ),
                    'update_products' => $this->productManagement->updateProducts( $input ),
                    'update_product_unit_quantities' => $this->productManagement->updateProductUnitQuantities( $input ),
                    'create_product' => $this->catalogCreation->createProduct( $input ),
                    'create_provider' => $this->catalogCreation->createProvider( $input ),
                    'create_customer' => $this->catalogCreation->createCustomer( $input ),
                    'import_products' => $this->catalogCreation->importProducts( $input ),
                    'generate_report' => $this->safeWrites->report( $input ),
                    'upload_media' => $this->safeWrites->upload( $input ),
                    'update_media' => $this->safeWrites->updateMedia( $user, $input ),
                    'create_unit_group' => $this->configuration()->saveUnitGroup( $user, null, $input ),
                    'update_unit_group' => $this->configuration()->saveUnitGroup( $user, (int) $input['id'], $input ),
                    'create_unit' => $this->configuration()->saveUnit( $user, null, $input ),
                    'update_unit' => $this->configuration()->saveUnit( $user, (int) $input['id'], $input ),
                    'create_tax_group' => $this->configuration()->saveTaxGroup( $user, null, $input ),
                    'update_tax_group' => $this->configuration()->saveTaxGroup( $user, (int) $input['id'], $input ),
                    'create_tax' => $this->configuration()->saveTax( $user, null, $input ),
                    'update_tax' => $this->configuration()->saveTax( $user, (int) $input['id'], $input ),
                    'create_coupon' => $this->configuration()->saveCoupon( $user, null, $input ),
                    'update_coupon' => $this->configuration()->saveCoupon( $user, (int) $input['id'], $input ),
                    'create_customer_group' => $this->configuration()->saveCustomerGroup( $user, null, $input ),
                    'update_customer_group' => $this->configuration()->saveCustomerGroup( $user, (int) $input['id'], $input ),
                    'delete_media' => $this->safeWrites->deleteMedia( $user, $input ),
                    default => throw new OxenException( 'NOT_FOUND', __m( 'Unknown Oxen write tool.', 'NsOxen' ), 404 ),
                };
                $expiresAt = $name === 'generate_report' && is_string( $result['expires_at'] ?? null )
                    ? Carbon::parse( $result['expires_at'] )
                    : now()->addDay();
                IdempotencyRecord::query()->create( ['user_id' => $user->id, 'tool' => $name, 'idempotency_key' => $key, 'input_hash' => $hash, 'result' => $result, 'expires_at' => $expiresAt] );

                return $result;
            } catch ( Throwable $exception ) {
                if ( $name === 'generate_report' && is_array( $result ) ) {
                    foreach ( array_filter( [$result['filename'] ?? null, $result['html_filename'] ?? null], 'is_string' ) as $filename ) {
                        Storage::disk( 'ns-temp' )->delete( 'mcp-reports/' . basename( $filename ) );
                    }
                }

                throw $exception;
            }
        }, 3 );
    }

    private function writeProcessingTool( User $user, string $name, array $input, string $hash ): array
    {
        $key = (string) ( $input['idempotency_key'] ?? '' );
        if ( $key === '' ) {
            throw new OxenException( 'VALIDATION_FAILED', __m( 'An idempotency key is required.', 'NsOxen' ) );
        }

        $existingResult = DB::transaction( function () use ( $user, $name, $key, $hash ): ?array {
            $existing = IdempotencyRecord::query()
                ->where( ['user_id' => $user->id, 'tool' => $name, 'idempotency_key' => $key] )
                ->lockForUpdate()
                ->first();

            if ( $existing && $existing->expires_at->isFuture() ) {
                if ( $existing->input_hash !== $hash ) {
                    throw new OxenException( 'CONFLICT', __m( 'The idempotency key was already used with different input.', 'NsOxen' ), 409 );
                }
                if ( data_get( $existing->result, '_state' ) === 'processing' ) {
                    throw new OxenException( 'CONFLICT', __m( 'This generated media action is already being processed.', 'NsOxen' ), 409 );
                }

                return $existing->result;
            }

            $existing?->delete();
            IdempotencyRecord::query()->create( [
                'user_id' => $user->id,
                'tool' => $name,
                'idempotency_key' => $key,
                'input_hash' => $hash,
                'result' => ['_state' => 'processing'],
                'expires_at' => now()->addDay(),
            ] );

            return null;
        }, 3 );

        if ( $existingResult !== null ) {
            return $existingResult;
        }

        try {
            $result = $name === 'generate_product_image'
                ? $this->safeWrites->generateProductImage( $user, $input )
                : $this->safeWrites->generateStoreLogo( $user, $input );
            IdempotencyRecord::query()->where( ['user_id' => $user->id, 'tool' => $name, 'idempotency_key' => $key] )->update( ['result' => $result, 'expires_at' => now()->addDay()] );

            return $result;
        } catch ( Throwable $exception ) {
            IdempotencyRecord::query()->where( ['user_id' => $user->id, 'tool' => $name, 'idempotency_key' => $key, 'input_hash' => $hash] )->delete();
            throw $exception;
        }
    }

    private function saveCategory( User $user, ?int $id, array $input ): array
    {
        $data = Validator::make( $input, ['name' => ['required', 'string', 'max:255'], 'parent_id' => ['nullable', 'integer', 'exists:nexopos_products_categories,id'], 'description' => ['nullable', 'string', 'max:1000']] )->validate();
        $category = $id ? ProductCategory::query()->findOrFail( $id ) : new ProductCategory;
        $category->fill( $data );
        $category->author_id = $user->id;
        $category->save();

        return $category->only( ['id', 'name', 'parent_id', 'description'] );
    }

    private function updateProduct( array $input ): array
    {
        $product = Product::query()->find( (int) ( $input['id'] ?? 0 ) ) ?? throw new OxenException( 'NOT_FOUND', __m( 'Product not found.', 'NsOxen' ), 404 );
        $data = Validator::make( $input, ['name' => ['sometimes', 'string', 'max:255'], 'description' => ['sometimes', 'nullable', 'string', 'max:2000'], 'status' => ['sometimes', 'in:available,unavailable'], 'category_id' => ['sometimes', 'integer', 'exists:nexopos_products_categories,id']] )->validate();
        $product->fill( Arr::except( $data, ['idempotency_key'] ) );
        $product->save();

        return $product->only( ['id', 'name', 'status', 'category_id'] );
    }

    private function updateSettings( array $input ): array
    {
        $allowed = ['ns_store_name', 'ns_currency_symbol', 'ns_currency_iso', 'ns_currency_precision', 'ns_date_format', 'ns_time_format', 'ns_timezone', 'ns_default_theme', 'ns_store_address', 'ns_store_phone', 'ns_store_email'];
        $settings = Validator::make( $input, ['settings' => ['required', 'array'], 'settings.*' => ['nullable', 'string', 'max:255']] )->validate()['settings'];
        foreach ( $settings as $key => $value ) {
            if ( ! in_array( $key, $allowed, true ) ) {
                throw new OxenException( 'VALIDATION_FAILED', __m( 'An unsupported setting was provided.', 'NsOxen' ) );
            }
            ns()->option->set( $key, $value );
        }

        return ['applied_keys' => array_keys( $settings )];
    }

    private function redact( array $input ): array
    {
        foreach ( ['api_key', 'base64_content', 'confirmation_token'] as $key ) {
            if ( array_key_exists( $key, $input ) ) {
                $input[$key] = '[REDACTED]';
            }
        }

        return $input;
    }

    /** @return array<string, OxenToolDefinition> */
    private function coreDefinitions(): array
    {
        $objectOutput = ['type' => 'object', 'additionalProperties' => true];
        $searchSchema = ['type' => 'object', 'properties' => ['search' => ['type' => ['string', 'null']], 'limit' => ['type' => ['integer', 'null'], 'minimum' => 1, 'maximum' => 50]], 'additionalProperties' => false];
        $idSchema = ['type' => 'object', 'properties' => ['id' => ['type' => 'integer']], 'required' => ['id'], 'additionalProperties' => false];
        $dateSchema = ['type' => 'object', 'properties' => ['date_start' => ['type' => ['string', 'null'], 'format' => 'date'], 'date_end' => ['type' => ['string', 'null'], 'format' => 'date']], 'additionalProperties' => false];
        $productIdsSchema = ['type' => 'array', 'minItems' => 1, 'maxItems' => 50, 'uniqueItems' => true, 'items' => ['type' => 'integer', 'minimum' => 1]];
        $productChangesSchema = [
            'type' => 'object',
            'minProperties' => 1,
            'properties' => [
                'category_id' => ['type' => 'integer', 'minimum' => 1],
                'auto_cogs' => ['type' => 'boolean'],
                'tax_group_id' => ['type' => ['integer', 'null'], 'minimum' => 1],
                'tax_type' => ['type' => 'string', 'enum' => ['inclusive', 'exclusive']],
                'expires' => ['type' => 'boolean'],
                'on_expiration' => ['type' => 'string', 'enum' => ['prevent_sales', 'allow_sales']],
                'barcode_type' => ['type' => 'string', 'enum' => ['ean8', 'ean13', 'codabar', 'code128', 'code39', 'code11', 'upca', 'upce']],
                'type' => ['type' => 'string', 'enum' => ['materialized', 'dematerialized']],
                'status' => ['type' => 'string', 'enum' => ['available', 'unavailable']],
                'stock_management' => ['type' => 'string', 'enum' => ['enabled', 'disabled']],
                'pinned' => ['type' => 'boolean'],
            ],
            'additionalProperties' => false,
        ];
        $unitChangesSchema = [
            'type' => 'object',
            'minProperties' => 1,
            'properties' => [
                'is_weighable' => ['type' => 'boolean'],
                'scale_plu' => ['type' => ['string', 'null'], 'pattern' => '^\\d+$'],
                'stock_alert_enabled' => ['type' => 'boolean'],
                'visible' => ['type' => 'boolean'],
            ],
            'additionalProperties' => false,
        ];

        $definitions = [
            $this->makeDefinition( 'search_products', 'Search products', 'Search products by name, SKU, or barcode with bounded results.', 'Products', 'nexopos.read.products', $searchSchema, ['search' => ['nullable', 'string', 'max:255'], 'limit' => ['nullable', 'integer', 'between:1,50']] ),
            $this->makeDefinition( 'get_product', 'Get product', 'Get one product by its identifier.', 'Products', 'nexopos.read.products', $idSchema, ['id' => ['required', 'integer', 'min:1']] ),
            $this->makeDefinition( 'get_low_stock_products', 'Low stock products', 'List inventory quantities at or below their low-stock threshold.', 'Inventory', 'nexopos.reports.low-stock', $searchSchema, ['search' => ['nullable', 'string', 'max:255'], 'limit' => ['nullable', 'integer', 'between:1,50']] ),
            $this->makeDefinition( 'search_customers', 'Search customers', 'Search customers by name, email, or phone.', 'Customers', 'nexopos.read.customers', $searchSchema, ['search' => ['nullable', 'string', 'max:255'], 'limit' => ['nullable', 'integer', 'between:1,50']] ),
            $this->makeDefinition( 'get_customer', 'Get customer', 'Get one customer and its account totals.', 'Customers', 'nexopos.read.customers', $idSchema, ['id' => ['required', 'integer', 'min:1']] ),
            $this->makeDefinition( 'search_orders', 'Search orders', 'Search orders by code, date range, or payment status.', 'Orders', 'nexopos.read.orders', ['type' => 'object', 'properties' => [...$searchSchema['properties'], ...$dateSchema['properties'], 'payment_status' => ['type' => ['string', 'null']]], 'additionalProperties' => false], ['search' => ['nullable', 'string', 'max:255'], 'date_start' => ['nullable', 'date'], 'date_end' => ['nullable', 'date', 'after_or_equal:date_start'], 'payment_status' => ['nullable', 'string', 'max:40'], 'limit' => ['nullable', 'integer', 'between:1,50']] ),
            $this->makeDefinition( 'get_order', 'Get order', 'Get an order with its line items.', 'Orders', 'nexopos.read.orders', $idSchema, ['id' => ['required', 'integer', 'min:1']] ),
            $this->makeDefinition( 'search_product_sales', 'Product sales', 'Return bounded aggregate product sales ranked by value.', 'Reports', 'nexopos.reports.products-report', $searchSchema, ['search' => ['nullable', 'string', 'max:255'], 'limit' => ['nullable', 'integer', 'between:1,50']] ),
            $this->makeDefinition( 'search_wallet_history', 'Wallet history', 'Return bounded customer wallet statement entries.', 'Customers', 'nexopos.read.customers', ['type' => 'object', 'properties' => ['customer_id' => ['type' => ['integer', 'null']], 'limit' => ['type' => ['integer', 'null'], 'minimum' => 1, 'maximum' => 50]], 'additionalProperties' => false], ['customer_id' => ['nullable', 'integer', 'min:1'], 'limit' => ['nullable', 'integer', 'between:1,50']] ),
            $this->makeDefinition( 'get_dashboard_summary', 'Sales summary', 'Return paid, unpaid, partially paid, tax, and income totals for a date range.', 'Reports', 'nexopos.reports.sales', $dateSchema, ['date_start' => ['nullable', 'date'], 'date_end' => ['nullable', 'date', 'after_or_equal:date_start']] ),
            $this->reportDefinition(),
            ...$this->creationDefinitions(),
            ...app( ConfigurationToolDefinitions::class )->definitions(),
            $this->makeDefinition( 'create_product_category', 'Create category', 'Create a product category after explicit approval.', 'Products', 'nexopos.create.categories', ['type' => 'object', 'properties' => ['name' => ['type' => 'string'], 'parent_id' => ['type' => ['integer', 'null']], 'description' => ['type' => ['string', 'null']], 'idempotency_key' => ['type' => 'string']], 'required' => ['name', 'idempotency_key'], 'additionalProperties' => false], ['name' => ['required', 'string', 'max:255'], 'parent_id' => ['nullable', 'integer'], 'description' => ['nullable', 'string', 'max:1000'], 'idempotency_key' => ['required', 'string', 'max:100']], 'write' ),
            $this->makeDefinition( 'update_product_category', 'Update category', 'Update a product category after explicit approval.', 'Products', 'nexopos.update.categories', ['type' => 'object', 'properties' => ['id' => ['type' => 'integer'], 'name' => ['type' => 'string'], 'parent_id' => ['type' => ['integer', 'null']], 'description' => ['type' => ['string', 'null']], 'idempotency_key' => ['type' => 'string']], 'required' => ['id', 'name', 'idempotency_key'], 'additionalProperties' => false], ['id' => ['required', 'integer', 'min:1'], 'name' => ['required', 'string', 'max:255'], 'parent_id' => ['nullable', 'integer'], 'description' => ['nullable', 'string', 'max:1000'], 'idempotency_key' => ['required', 'string', 'max:100']], 'write' ),
            $this->makeDefinition( 'update_product', 'Update product', 'Update allowlisted product fields after explicit approval.', 'Products', 'nexopos.update.products', ['type' => 'object', 'properties' => ['id' => ['type' => 'integer'], 'name' => ['type' => ['string', 'null']], 'description' => ['type' => ['string', 'null']], 'status' => ['type' => ['string', 'null']], 'category_id' => ['type' => ['integer', 'null']], 'idempotency_key' => ['type' => 'string']], 'required' => ['id', 'idempotency_key'], 'additionalProperties' => false], ['id' => ['required', 'integer', 'min:1'], 'name' => ['sometimes', 'string', 'max:255'], 'description' => ['sometimes', 'nullable', 'string', 'max:2000'], 'status' => ['sometimes', 'in:available,unavailable'], 'category_id' => ['sometimes', 'integer'], 'idempotency_key' => ['required', 'string', 'max:100']], 'write' ),
            $this->makeDefinition( 'update_products', 'Update products', 'Atomically update allowlisted catalog settings for 1 to 50 products after explicit approval. Catalog discounts are not supported.', 'Products', 'nexopos.update.products', ['type' => 'object', 'properties' => ['product_ids' => $productIdsSchema, 'changes' => $productChangesSchema, 'idempotency_key' => ['type' => 'string']], 'required' => ['product_ids', 'changes', 'idempotency_key'], 'additionalProperties' => false], ['product_ids' => ['required', 'array', 'between:1,50'], 'product_ids.*' => ['required', 'integer', 'min:1', 'distinct'], 'changes' => ['required', 'array:category_id,auto_cogs,tax_group_id,tax_type,expires,on_expiration,barcode_type,type,status,stock_management,pinned', 'min:1'], 'changes.category_id' => ['sometimes', 'integer', 'min:1'], 'changes.auto_cogs' => ['sometimes', 'boolean'], 'changes.tax_group_id' => ['sometimes', 'nullable', 'integer', 'min:1'], 'changes.tax_type' => ['sometimes', 'in:inclusive,exclusive'], 'changes.expires' => ['sometimes', 'boolean'], 'changes.on_expiration' => ['sometimes', 'in:prevent_sales,allow_sales'], 'changes.barcode_type' => ['sometimes', 'in:ean8,ean13,codabar,code128,code39,code11,upca,upce'], 'changes.type' => ['sometimes', 'in:materialized,dematerialized'], 'changes.status' => ['sometimes', 'in:available,unavailable'], 'changes.stock_management' => ['sometimes', 'in:enabled,disabled'], 'changes.pinned' => ['sometimes', 'boolean'], 'idempotency_key' => ['required', 'string', 'max:100']], 'write' ),
            $this->makeDefinition( 'update_product_unit_quantities', 'Update product unit settings', 'Atomically update weighing, stock-alert, and visibility settings for 1 to 50 product unit quantities after explicit approval.', 'Products', 'nexopos.update.products', ['type' => 'object', 'properties' => ['unit_quantity_ids' => $productIdsSchema, 'changes' => $unitChangesSchema, 'idempotency_key' => ['type' => 'string']], 'required' => ['unit_quantity_ids', 'changes', 'idempotency_key'], 'additionalProperties' => false], ['unit_quantity_ids' => ['required', 'array', 'between:1,50'], 'unit_quantity_ids.*' => ['required', 'integer', 'min:1', 'distinct'], 'changes' => ['required', 'array:is_weighable,scale_plu,stock_alert_enabled,visible', 'min:1'], 'changes.is_weighable' => ['sometimes', 'boolean'], 'changes.scale_plu' => ['sometimes', 'nullable', 'string', 'regex:/^\\d+$/', 'max:10'], 'changes.stock_alert_enabled' => ['sometimes', 'boolean'], 'changes.visible' => ['sometimes', 'boolean'], 'idempotency_key' => ['required', 'string', 'max:100']], 'write' ),
            $this->makeDefinition( 'generate_product_image', 'Generate product image', 'Generate a 1024×1024 PNG with OpenAI and assign it as the product primary image after explicit confirmation.', 'Products', ['nexopos.update.products', 'nexopos.upload.medias'], ['type' => 'object', 'properties' => ['product_id' => ['type' => 'integer', 'minimum' => 1], 'prompt' => ['type' => 'string', 'minLength' => 3, 'maxLength' => 2000], 'idempotency_key' => ['type' => 'string']], 'required' => ['product_id', 'prompt', 'idempotency_key'], 'additionalProperties' => false], ['product_id' => ['required', 'integer', 'min:1'], 'prompt' => ['required', 'string', 'min:3', 'max:2000'], 'idempotency_key' => ['required', 'string', 'max:100']], 'write', true ),
            $this->makeDefinition( 'update_settings', 'Update store settings', 'Propose changes to allowlisted store settings. Use ns_store_name for the visible store name.', 'Settings', 'manage.options', ['type' => 'object', 'properties' => ['settings' => ['type' => 'object', 'properties' => collect( ['ns_store_name', 'ns_currency_symbol', 'ns_currency_iso', 'ns_currency_precision', 'ns_date_format', 'ns_time_format', 'ns_timezone', 'ns_default_theme', 'ns_store_address', 'ns_store_phone', 'ns_store_email'] )->mapWithKeys( static fn ( string $key ): array => [$key => ['type' => ['string', 'null']]] )->all(), 'minProperties' => 1, 'additionalProperties' => false], 'idempotency_key' => ['type' => 'string']], 'required' => ['settings', 'idempotency_key'], 'additionalProperties' => false], ['settings' => ['required', 'array', 'min:1', 'max:20'], 'settings.*' => ['nullable', 'string', 'max:255'], 'idempotency_key' => ['required', 'string', 'max:100']], 'write' ),
            $this->makeDefinition( 'upload_media', 'Upload media', 'Upload an allowlisted base64 image or PDF up to 2 MB after approval.', 'Media', 'nexopos.upload.medias', ['type' => 'object', 'properties' => ['name' => ['type' => ['string', 'null']], 'base64_content' => ['type' => 'string'], 'idempotency_key' => ['type' => 'string']], 'required' => ['base64_content', 'idempotency_key'], 'additionalProperties' => false], ['name' => ['nullable', 'string', 'max:120'], 'base64_content' => ['required', 'string'], 'idempotency_key' => ['required', 'string', 'max:100']], 'write' ),
            $this->makeDefinition( 'delete_media', 'Delete media', 'Permanently delete one media record after explicit confirmation.', 'Media', 'nexopos.delete.medias', ['type' => 'object', 'properties' => ['media_id' => ['type' => 'integer'], 'idempotency_key' => ['type' => 'string']], 'required' => ['media_id', 'idempotency_key'], 'additionalProperties' => false], ['media_id' => ['required', 'integer', 'min:1'], 'idempotency_key' => ['required', 'string', 'max:100']], 'destructive', true ),
        ];

        foreach ( [
            ['search_stock_history', 'Stock history', 'Search bounded inventory movement history.', 'Inventory', 'nexopos.read.products-history'],
            ['search_categories', 'Product categories', 'Search product categories.', 'Products', 'nexopos.read.categories'],
            ['search_units', 'Product units', 'Search product units.', 'Products', 'nexopos.read.products-units'],
            ['search_unit_groups', 'Unit groups', 'Search product unit groups.', 'Products', 'nexopos.read.products-units'],
            ['search_customer_groups', 'Customer groups', 'Search customer groups.', 'Customers', 'nexopos.read.customers-groups'],
            ['search_rewards', 'Reward systems', 'Search customer reward systems.', 'Customers', 'nexopos.read.rewards'],
            ['search_coupons', 'Coupons', 'Search configured coupons.', 'Customers', 'nexopos.read.coupons'],
            ['search_providers', 'Providers', 'Search providers and account totals.', 'Procurements', 'nexopos.read.providers'],
            ['search_procurements', 'Procurements', 'Search procurements and delivery status.', 'Procurements', 'nexopos.read.procurements'],
            ['search_registers', 'Registers', 'Search cash registers and current balances.', 'Registers', 'nexopos.read.registers'],
            ['search_register_history', 'Register history', 'Search bounded cash register history.', 'Registers', 'nexopos.read.registers-history'],
            ['search_taxes', 'Taxes', 'Search taxes and rates.', 'Settings', 'nexopos.read.taxes'],
            ['search_media', 'Media', 'Search bounded media records.', 'Media', 'nexopos.see.medias'],
            ['search_installments', 'Order installments', 'Search bounded order installment schedules.', 'Orders', 'nexopos.read.orders-instalments'],
            ['search_transactions', 'Transactions', 'Search bounded income and expense transactions.', 'Accounting', 'nexopos.read.transactions'],
            ['search_accounts', 'Transaction accounts', 'Search transaction accounts and categories.', 'Accounting', 'nexopos.read.transactions-account'],
        ] as [$name, $title, $description, $category, $permission] ) {
            $definitions[] = $this->makeDefinition( $name, $title, $description, $category, $permission, $searchSchema, ['search' => ['nullable', 'string', 'max:255'], 'limit' => ['nullable', 'integer', 'between:1,50']] );
        }

        $definitions[] = $this->makeDefinition( 'get_inventory_quantities', 'Inventory quantities', 'Search current product-unit inventory with product and unit labels, prices, weighing/PLU state, stock alerts, visibility, and expiration.', 'Inventory', 'nexopos.read.products', ['type' => 'object', 'properties' => ['search' => ['type' => ['string', 'null']], 'product_ids' => $productIdsSchema, 'unit_id' => ['type' => ['integer', 'null'], 'minimum' => 1], 'limit' => ['type' => ['integer', 'null'], 'minimum' => 1, 'maximum' => 50]], 'additionalProperties' => false], ['search' => ['nullable', 'string', 'max:255'], 'product_ids' => ['nullable', 'array', 'between:1,50'], 'product_ids.*' => ['integer', 'min:1', 'distinct'], 'unit_id' => ['nullable', 'integer', 'min:1'], 'limit' => ['nullable', 'integer', 'between:1,50']] );

        $definitions[] = $this->makeDefinition( 'get_product_sales_performance', 'Product sales performance', 'Find products with zero or low sales in an explicit date range. Returns product identifiers, quantities, revenue, pin state, and remaining POS pin capacity for safe follow-up updates.', 'Reports', 'nexopos.reports.products-report', ['type' => 'object', 'properties' => [
            'date_start' => ['type' => 'string', 'format' => 'date'],
            'date_end' => ['type' => 'string', 'format' => 'date'],
            'include_zero_sales' => ['type' => ['boolean', 'null']],
            'category_id' => ['type' => ['integer', 'null'], 'minimum' => 1],
            'status' => ['type' => ['string', 'null'], 'enum' => ['available', 'unavailable', null]],
            'pinned' => ['type' => ['boolean', 'null']],
            'max_quantity' => ['type' => ['number', 'null'], 'minimum' => 0],
            'max_revenue' => ['type' => ['number', 'null'], 'minimum' => 0],
            'page' => ['type' => ['integer', 'null'], 'minimum' => 1],
            'limit' => ['type' => ['integer', 'null'], 'minimum' => 1, 'maximum' => 50],
        ], 'required' => ['date_start', 'date_end'], 'additionalProperties' => false], [
            'date_start' => ['required', 'date'], 'date_end' => ['required', 'date', 'after_or_equal:date_start'],
            'include_zero_sales' => ['nullable', 'boolean'], 'category_id' => ['nullable', 'integer', 'min:1'],
            'status' => ['nullable', 'in:available,unavailable'], 'pinned' => ['nullable', 'boolean'],
            'max_quantity' => ['nullable', 'numeric', 'min:0'], 'max_revenue' => ['nullable', 'numeric', 'min:0'],
            'page' => ['nullable', 'integer', 'min:1'], 'limit' => ['nullable', 'integer', 'between:1,50'],
        ] );

        $definitions[] = $this->makeDefinition( 'get_payment_method_totals', 'Payment method totals', 'Aggregate payment counts and values by payment method.', 'Reports', 'nexopos.reports.sales', $searchSchema, ['limit' => ['nullable', 'integer', 'between:1,50']] );
        $definitions[] = $this->makeDefinition( 'get_cashier_ranking', 'Cashier ranking', 'Rank cashiers by order count and sales value for a date range.', 'Reports', 'nexopos.reports.sales', ['type' => 'object', 'properties' => [...$dateSchema['properties'], 'limit' => ['type' => ['integer', 'null'], 'minimum' => 1, 'maximum' => 50]], 'additionalProperties' => false], ['date_start' => ['nullable', 'date'], 'date_end' => ['nullable', 'date', 'after_or_equal:date_start'], 'limit' => ['nullable', 'integer', 'between:1,50']] );
        $definitions[] = $this->makeDefinition( 'get_refund_summary', 'Refund summary', 'Return refund count and value for a date range.', 'Reports', 'nexopos.reports.sales', $dateSchema, ['date_start' => ['nullable', 'date'], 'date_end' => ['nullable', 'date', 'after_or_equal:date_start']] );
        $definitions[] = $this->makeDefinition( 'get_yearly_sales', 'Yearly sales', 'Return monthly sales, order, and tax totals for a year.', 'Reports', 'nexopos.reports.sales', ['type' => 'object', 'properties' => ['year' => ['type' => 'integer', 'minimum' => 2000, 'maximum' => 2200]], 'additionalProperties' => false], ['year' => ['nullable', 'integer', 'between:2000,2200']] );
        $definitions[] = $this->makeDefinition( 'compare_sales_periods', 'Compare sales periods', 'Compare sales totals across two explicit date ranges.', 'Reports', 'nexopos.reports.sales', ['type' => 'object', 'properties' => ['current_start' => ['type' => 'string', 'format' => 'date'], 'current_end' => ['type' => 'string', 'format' => 'date'], 'previous_start' => ['type' => 'string', 'format' => 'date'], 'previous_end' => ['type' => 'string', 'format' => 'date']], 'required' => ['current_start', 'current_end', 'previous_start', 'previous_end'], 'additionalProperties' => false], ['current_start' => ['required', 'date'], 'current_end' => ['required', 'date', 'after_or_equal:current_start'], 'previous_start' => ['required', 'date'], 'previous_end' => ['required', 'date', 'after_or_equal:previous_start']] );
        $definitions[] = $this->makeDefinition( 'get_profit_summary', 'Profit summary', 'Return sales, cost, and gross profit for a date range.', 'Reports', 'nexopos.reports.sales', $dateSchema, ['date_start' => ['nullable', 'date'], 'date_end' => ['nullable', 'date', 'after_or_equal:date_start']] );
        $definitions[] = $this->makeDefinition( 'get_store_settings', 'Store settings', 'Return a safe allowlist of active store settings.', 'Settings', 'manage.options', ['type' => 'object', 'properties' => [], 'additionalProperties' => false], [] );

        return collect( $definitions )->keyBy( fn ( OxenToolDefinition $definition ): string => $definition->name )->all();
    }

    /** @return list<OxenToolDefinition> */
    private function creationDefinitions(): array
    {
        $addressProperties = collect( ['first_name', 'last_name', 'phone', 'address_1', 'address_2', 'country', 'city', 'pobox', 'company', 'email'] )
            ->mapWithKeys( static fn ( string $field ): array => [$field => ['type' => ['string', 'null'], 'maxLength' => 255]] )
            ->all();
        $addressSchema = ['type' => 'object', 'properties' => $addressProperties, 'additionalProperties' => false];
        $sellingUnitProperties = [
            'unit_id' => ['type' => 'integer', 'minimum' => 1],
            'sale_price' => ['type' => 'number', 'minimum' => 0],
            'wholesale_price' => ['type' => ['number', 'null'], 'minimum' => 0],
            'convert_unit_id' => ['type' => ['integer', 'null'], 'minimum' => 1],
            'barcode' => ['type' => ['string', 'null'], 'maxLength' => 255],
            'cogs' => ['type' => ['number', 'null'], 'minimum' => 0],
            'low_quantity' => ['type' => ['number', 'null'], 'minimum' => 0],
            'stock_alert_enabled' => ['type' => ['boolean', 'null']],
            'visible' => ['type' => ['boolean', 'null']],
            'is_weighable' => ['type' => ['boolean', 'null']],
            'scale_plu' => ['type' => ['string', 'null'], 'pattern' => '^\\d+$'],
        ];
        $sellingUnitSchema = [
            'type' => 'object',
            'properties' => $sellingUnitProperties,
            'required' => ['unit_id', 'sale_price'],
            'additionalProperties' => false,
        ];
        $productProperties = [
            'name' => ['type' => 'string', 'minLength' => 1, 'maxLength' => 255],
            'category_id' => ['type' => 'integer', 'minimum' => 1],
            'unit_group_id' => ['type' => 'integer', 'minimum' => 1],
            'barcode' => ['type' => ['string', 'null'], 'maxLength' => 255],
            'sku' => ['type' => ['string', 'null'], 'maxLength' => 255],
            'barcode_type' => ['type' => ['string', 'null'], 'enum' => ['ean8', 'ean13', 'codabar', 'code128', 'code39', 'code11', 'upca', 'upce', null]],
            'type' => ['type' => ['string', 'null'], 'enum' => ['materialized', 'dematerialized', null]],
            'status' => ['type' => ['string', 'null'], 'enum' => ['available', 'unavailable', null]],
            'stock_management' => ['type' => ['string', 'null'], 'enum' => ['enabled', 'disabled', null]],
            'pinned' => ['type' => ['boolean', 'null']],
            'description' => ['type' => ['string', 'null'], 'maxLength' => 2000],
            'expires' => ['type' => ['boolean', 'null']],
            'on_expiration' => ['type' => ['string', 'null'], 'enum' => ['prevent_sales', 'allow_sales', null]],
            'tax_group_id' => ['type' => ['integer', 'null'], 'minimum' => 1],
            'tax_type' => ['type' => ['string', 'null'], 'enum' => ['inclusive', 'exclusive', null]],
            'accurate_tracking' => ['type' => ['boolean', 'null']],
            'auto_cogs' => ['type' => ['boolean', 'null']],
            'units' => ['type' => 'array', 'minItems' => 1, 'maxItems' => 20, 'items' => $sellingUnitSchema],
            'idempotency_key' => ['type' => 'string'],
        ];
        $productRules = $this->productCreationRules();

        $dependencySchema = [
            'type' => 'object',
            'properties' => [
                'id' => ['type' => ['integer', 'null'], 'minimum' => 1],
                'name' => ['type' => ['string', 'null'], 'maxLength' => 255],
                'description' => ['type' => ['string', 'null'], 'maxLength' => 1000],
                'parent_id' => ['type' => ['integer', 'null'], 'minimum' => 1],
                'create_if_missing' => ['type' => ['boolean', 'null']],
            ],
            'additionalProperties' => false,
        ];
        $importUnitSchema = $sellingUnitSchema;
        $importUnitSchema['properties'] = [
            ...$sellingUnitProperties,
            'name' => ['type' => ['string', 'null'], 'maxLength' => 255],
            'identifier' => ['type' => ['string', 'null'], 'maxLength' => 100],
            'value' => ['type' => ['number', 'null'], 'exclusiveMinimum' => 0],
            'base_unit' => ['type' => ['boolean', 'null']],
            'description' => ['type' => ['string', 'null'], 'maxLength' => 1000],
            'create_if_missing' => ['type' => ['boolean', 'null']],
        ];
        $importUnitSchema['required'] = ['sale_price'];
        $importProductProperties = Arr::except( $productProperties, ['category_id', 'unit_group_id', 'idempotency_key'] );
        $importProductProperties['reference'] = ['type' => ['string', 'null'], 'maxLength' => 100];
        $importProductProperties['category'] = $dependencySchema;
        $importProductProperties['unit_group'] = $dependencySchema;
        $importProductProperties['units'] = ['type' => 'array', 'minItems' => 1, 'maxItems' => 20, 'items' => $importUnitSchema];

        return [
            $this->makeDefinition(
                'create_product',
                'Create product',
                'Create one simple materialized or dematerialized product using existing category and unit identifiers. Initial stock, grouped products, variables, and catalog discounts are not supported.',
                'Products',
                'nexopos.create.products',
                ['type' => 'object', 'properties' => $productProperties, 'required' => ['name', 'category_id', 'unit_group_id', 'units', 'idempotency_key'], 'additionalProperties' => false],
                $productRules,
                'write',
                true,
            ),
            $this->makeDefinition(
                'create_provider',
                'Create provider',
                'Create a provider from strictly allowlisted contact fields after approval.',
                'Procurements',
                'nexopos.create.providers',
                ['type' => 'object', 'properties' => [
                    'first_name' => ['type' => 'string'], 'last_name' => ['type' => ['string', 'null']],
                    'email' => ['type' => ['string', 'null']], 'phone' => ['type' => ['string', 'null']],
                    'address_1' => ['type' => ['string', 'null']], 'address_2' => ['type' => ['string', 'null']],
                    'description' => ['type' => ['string', 'null']], 'idempotency_key' => ['type' => 'string'],
                ], 'required' => ['first_name', 'idempotency_key'], 'additionalProperties' => false],
                ['first_name' => ['required', 'string', 'max:255'], 'last_name' => ['nullable', 'string', 'max:255'], 'email' => ['nullable', 'email', 'max:255', 'unique:nexopos_providers,email'], 'phone' => ['nullable', 'string', 'max:100'], 'address_1' => ['nullable', 'string', 'max:255'], 'address_2' => ['nullable', 'string', 'max:255'], 'description' => ['nullable', 'string', 'max:2000'], 'idempotency_key' => ['required', 'string', 'max:100']],
                'write',
                true,
            ),
            $this->makeDefinition(
                'create_customer',
                'Create customer',
                'Create a customer and optional billing or shipping addresses. Passwords and store identifiers are never accepted.',
                'Customers',
                'nexopos.create.customers',
                ['type' => 'object', 'properties' => [
                    'first_name' => ['type' => 'string'], 'last_name' => ['type' => ['string', 'null']],
                    'email' => ['type' => ['string', 'null']], 'phone' => ['type' => ['string', 'null']],
                    'pobox' => ['type' => ['string', 'null']], 'gender' => ['type' => ['string', 'null'], 'enum' => ['male', 'female', null]],
                    'birth_date' => ['type' => ['string', 'null'], 'format' => 'date'], 'credit_limit_amount' => ['type' => ['number', 'null'], 'minimum' => 0],
                    'description' => ['type' => ['string', 'null']], 'active' => ['type' => ['boolean', 'null']],
                    'group_id' => ['type' => ['integer', 'null'], 'minimum' => 1],
                    'address' => ['type' => ['object', 'null'], 'properties' => ['billing' => $addressSchema, 'shipping' => $addressSchema], 'additionalProperties' => false],
                    'idempotency_key' => ['type' => 'string'],
                ], 'required' => ['first_name', 'idempotency_key'], 'additionalProperties' => false],
                $this->customerCreationRules(),
                'write',
                true,
            ),
            $this->makeDefinition(
                'import_products',
                'Import products',
                'Atomically import 1 to 50 simple products, resolving or explicitly creating categories, unit groups, and units. The full import rolls back on any failure.',
                'Products',
                ['nexopos.create.products', 'nexopos.create.categories', 'nexopos.create.products-units'],
                ['type' => 'object', 'properties' => [
                    'products' => ['type' => 'array', 'minItems' => 1, 'maxItems' => 50, 'items' => ['type' => 'object', 'properties' => $importProductProperties, 'required' => ['name', 'category', 'unit_group', 'units'], 'additionalProperties' => false]],
                    'idempotency_key' => ['type' => 'string'],
                ], 'required' => ['products', 'idempotency_key'], 'additionalProperties' => false],
                $this->importCreationRules(),
                'write',
                true,
            ),
        ];
    }

    private function reportDefinition(): OxenToolDefinition
    {
        $dataPoint = [
            'type' => 'object',
            'properties' => [
                'label' => ['type' => 'string', 'maxLength' => 120],
                'value' => ['type' => 'number'],
                'detail' => ['type' => ['string', 'null'], 'maxLength' => 255],
                'color' => ['type' => ['string', 'null'], 'maxLength' => 32],
            ],
            'required' => ['label', 'value'],
            'additionalProperties' => false,
        ];
        $section = [
            'type' => 'object',
            'properties' => [
                'type' => ['type' => 'string', 'enum' => ['text', 'kpi_grid', 'table', 'bar_chart', 'pie_chart', 'page_break']],
                'title' => ['type' => ['string', 'null'], 'maxLength' => 120],
                'description' => ['type' => ['string', 'null'], 'maxLength' => 500],
                'content' => ['type' => ['string', 'null'], 'maxLength' => 5000],
                'columns' => ['type' => ['array', 'null'], 'maxItems' => 20, 'items' => ['type' => 'string', 'maxLength' => 120]],
                'rows' => ['type' => ['array', 'null'], 'maxItems' => 100, 'items' => ['type' => 'object', 'additionalProperties' => ['type' => ['string', 'number', 'integer', 'boolean', 'null']]]],
                'items' => ['type' => ['array', 'null'], 'maxItems' => 20, 'items' => $dataPoint],
            ],
            'required' => ['type'],
            'additionalProperties' => false,
        ];

        return $this->makeDefinition(
            'generate_report',
            'Generate report',
            'Generate a branded PDF with HTML preview, or an optional CSV export, from bounded text, KPI, table, bar-chart, pie-chart, and page-break sections.',
            'Exports',
            'nexopos.reports.sales',
            ['type' => 'object', 'properties' => [
                'title' => ['type' => 'string', 'minLength' => 1, 'maxLength' => 120],
                'subtitle' => ['type' => ['string', 'null'], 'maxLength' => 255],
                'period_label' => ['type' => ['string', 'null'], 'maxLength' => 120],
                'prepared_by' => ['type' => ['string', 'null'], 'maxLength' => 120],
                'filename' => ['type' => ['string', 'null'], 'maxLength' => 120],
                'format' => ['type' => ['string', 'null'], 'enum' => ['pdf', 'csv']],
                'expires_in_minutes' => ['type' => ['integer', 'null'], 'minimum' => 5, 'maximum' => 1440],
                'sections' => ['type' => 'array', 'minItems' => 1, 'maxItems' => 10, 'items' => $section],
                'idempotency_key' => ['type' => 'string'],
            ], 'required' => ['title', 'sections', 'idempotency_key'], 'additionalProperties' => false],
            [
                'title' => ['required', 'string', 'max:120'], 'subtitle' => ['nullable', 'string', 'max:255'],
                'period_label' => ['nullable', 'string', 'max:120'], 'prepared_by' => ['nullable', 'string', 'max:120'],
                'filename' => ['nullable', 'string', 'max:120'], 'format' => ['nullable', 'in:pdf,csv'],
                'expires_in_minutes' => ['nullable', 'integer', 'between:5,1440'],
                'sections' => ['required', 'array', 'between:1,10'],
                'sections.*' => ['required', 'array:type,title,description,content,columns,rows,items'],
                'sections.*.type' => ['required', 'in:text,kpi_grid,table,bar_chart,pie_chart,page_break'],
                'sections.*.title' => ['nullable', 'string', 'max:120'], 'sections.*.description' => ['nullable', 'string', 'max:500'],
                'sections.*.content' => ['nullable', 'string', 'max:5000'], 'sections.*.columns' => ['nullable', 'array', 'max:20'],
                'sections.*.columns.*' => ['string', 'max:120'], 'sections.*.rows' => ['nullable', 'array', 'max:100'],
                'sections.*.rows.*' => ['array', 'max:20'], 'sections.*.rows.*.*' => ['nullable'],
                'sections.*.items' => ['nullable', 'array', 'max:20'], 'sections.*.items.*' => ['array:label,value,detail,color'],
                'sections.*.items.*.label' => ['required', 'string', 'max:120'], 'sections.*.items.*.value' => ['required', 'numeric'],
                'sections.*.items.*.detail' => ['nullable', 'string', 'max:255'], 'sections.*.items.*.color' => ['nullable', 'string', 'max:32'],
                'idempotency_key' => ['required', 'string', 'max:100'],
            ],
            'write',
        );
    }

    /** @return array<string, mixed> */
    private function productCreationRules( string $prefix = '' ): array
    {
        $field = static fn ( string $name ): string => $prefix . $name;

        return [
            $field( 'name' ) => ['required', 'string', 'max:255'],
            $field( 'category_id' ) => ['required', 'integer', 'min:1'],
            $field( 'unit_group_id' ) => ['required', 'integer', 'min:1'],
            $field( 'barcode' ) => ['nullable', 'string', 'max:255'],
            $field( 'sku' ) => ['nullable', 'string', 'max:255'],
            $field( 'barcode_type' ) => ['nullable', 'in:ean8,ean13,codabar,code128,code39,code11,upca,upce'],
            $field( 'type' ) => ['nullable', 'in:materialized,dematerialized'],
            $field( 'status' ) => ['nullable', 'in:available,unavailable'],
            $field( 'stock_management' ) => ['nullable', 'in:enabled,disabled'],
            $field( 'pinned' ) => ['nullable', 'boolean'],
            $field( 'description' ) => ['nullable', 'string', 'max:2000'],
            $field( 'expires' ) => ['nullable', 'boolean'],
            $field( 'on_expiration' ) => ['nullable', 'in:prevent_sales,allow_sales'],
            $field( 'tax_group_id' ) => ['nullable', 'integer', 'min:1'],
            $field( 'tax_type' ) => ['nullable', 'in:inclusive,exclusive'],
            $field( 'accurate_tracking' ) => ['nullable', 'boolean'],
            $field( 'auto_cogs' ) => ['nullable', 'boolean'],
            $field( 'units' ) => ['required', 'array', 'between:1,20'],
            $field( 'units.*.unit_id' ) => ['required', 'integer', 'min:1', 'distinct'],
            $field( 'units.*.sale_price' ) => ['required', 'numeric', 'min:0'],
            $field( 'units.*.wholesale_price' ) => ['nullable', 'numeric', 'min:0'],
            $field( 'units.*.convert_unit_id' ) => ['nullable', 'integer', 'min:1'],
            $field( 'units.*.barcode' ) => ['nullable', 'string', 'max:255', 'distinct'],
            $field( 'units.*.cogs' ) => ['nullable', 'numeric', 'min:0'],
            $field( 'units.*.low_quantity' ) => ['nullable', 'numeric', 'min:0'],
            $field( 'units.*.stock_alert_enabled' ) => ['nullable', 'boolean'],
            $field( 'units.*.visible' ) => ['nullable', 'boolean'],
            $field( 'units.*.is_weighable' ) => ['nullable', 'boolean'],
            $field( 'units.*.scale_plu' ) => ['nullable', 'string', 'regex:/^\\d+$/', 'max:10'],
            'idempotency_key' => ['required', 'string', 'max:100'],
        ];
    }

    /** @return array<string, mixed> */
    private function customerCreationRules(): array
    {
        $phoneRules = ['nullable', 'string', 'max:100'];
        if ( ns()->option->get( 'ns_customers_force_unique_phone', 'no' ) === 'yes' ) {
            $phoneRules[] = 'unique:nexopos_users,phone';
        }
        $rules = [
            'first_name' => ['required', 'string', 'max:255'], 'last_name' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'], 'phone' => $phoneRules,
            'pobox' => ['nullable', 'string', 'max:100'], 'gender' => ['nullable', 'in:male,female'],
            'birth_date' => ['nullable', 'date'], 'credit_limit_amount' => ['nullable', 'numeric', 'min:0'],
            'description' => ['nullable', 'string', 'max:2000'], 'active' => ['nullable', 'boolean'],
            'group_id' => ['nullable', 'integer', 'min:1'], 'address' => ['nullable', 'array:billing,shipping'],
            'idempotency_key' => ['required', 'string', 'max:100'],
        ];
        foreach ( ['billing', 'shipping'] as $type ) {
            $rules["address.{$type}"] = ['nullable', 'array:first_name,last_name,phone,address_1,address_2,country,city,pobox,company,email'];
            foreach ( ['first_name', 'last_name', 'phone', 'address_1', 'address_2', 'country', 'city', 'pobox', 'company'] as $field ) {
                $rules["address.{$type}.{$field}"] = ['nullable', 'string', 'max:255'];
            }
            $rules["address.{$type}.email"] = ['nullable', 'email', 'max:255'];
        }

        return $rules;
    }

    /** @return array<string, mixed> */
    private function importCreationRules(): array
    {
        return [
            'products' => ['required', 'array', 'between:1,50'],
            'products.*' => ['required', 'array:reference,name,category,unit_group,barcode,sku,barcode_type,type,status,stock_management,pinned,description,expires,on_expiration,tax_group_id,tax_type,accurate_tracking,auto_cogs,units'],
            'products.*.reference' => ['nullable', 'string', 'max:100', 'distinct'],
            'products.*.name' => ['required', 'string', 'max:255'],
            'products.*.category' => ['required', 'array:id,name,description,parent_id,create_if_missing'],
            'products.*.category.id' => ['nullable', 'integer', 'min:1'],
            'products.*.category.name' => ['required_without:products.*.category.id', 'nullable', 'string', 'max:255'],
            'products.*.category.create_if_missing' => ['nullable', 'boolean'],
            'products.*.unit_group' => ['required', 'array:id,name,description,parent_id,create_if_missing'],
            'products.*.unit_group.id' => ['nullable', 'integer', 'min:1'],
            'products.*.unit_group.name' => ['required_without:products.*.unit_group.id', 'nullable', 'string', 'max:255'],
            'products.*.unit_group.create_if_missing' => ['nullable', 'boolean'],
            'products.*.barcode' => ['nullable', 'string', 'max:255'],
            'products.*.sku' => ['nullable', 'string', 'max:255'],
            'products.*.barcode_type' => ['nullable', 'in:ean8,ean13,codabar,code128,code39,code11,upca,upce'],
            'products.*.type' => ['nullable', 'in:materialized,dematerialized'],
            'products.*.status' => ['nullable', 'in:available,unavailable'],
            'products.*.stock_management' => ['nullable', 'in:enabled,disabled'],
            'products.*.pinned' => ['nullable', 'boolean'],
            'products.*.description' => ['nullable', 'string', 'max:2000'],
            'products.*.expires' => ['nullable', 'boolean'],
            'products.*.on_expiration' => ['nullable', 'in:prevent_sales,allow_sales'],
            'products.*.tax_group_id' => ['nullable', 'integer', 'min:1'],
            'products.*.tax_type' => ['nullable', 'in:inclusive,exclusive'],
            'products.*.accurate_tracking' => ['nullable', 'boolean'],
            'products.*.auto_cogs' => ['nullable', 'boolean'],
            'products.*.units' => ['required', 'array', 'between:1,20'],
            'products.*.units.*' => ['required', 'array:unit_id,name,identifier,value,base_unit,description,create_if_missing,sale_price,wholesale_price,convert_unit_id,barcode,cogs,low_quantity,stock_alert_enabled,visible,is_weighable,scale_plu'],
            'products.*.units.*.unit_id' => ['nullable', 'integer', 'min:1'],
            'products.*.units.*.name' => ['required_without:products.*.units.*.unit_id', 'nullable', 'string', 'max:255'],
            'products.*.units.*.identifier' => ['nullable', 'string', 'max:100'],
            'products.*.units.*.value' => ['nullable', 'numeric', 'gt:0'],
            'products.*.units.*.base_unit' => ['nullable', 'boolean'],
            'products.*.units.*.create_if_missing' => ['nullable', 'boolean'],
            'products.*.units.*.sale_price' => ['required', 'numeric', 'min:0'],
            'products.*.units.*.wholesale_price' => ['nullable', 'numeric', 'min:0'],
            'products.*.units.*.convert_unit_id' => ['nullable', 'integer', 'min:1'],
            'products.*.units.*.barcode' => ['nullable', 'string', 'max:255'],
            'products.*.units.*.cogs' => ['nullable', 'numeric', 'min:0'],
            'products.*.units.*.low_quantity' => ['nullable', 'numeric', 'min:0'],
            'products.*.units.*.stock_alert_enabled' => ['nullable', 'boolean'],
            'products.*.units.*.visible' => ['nullable', 'boolean'],
            'products.*.units.*.is_weighable' => ['nullable', 'boolean'],
            'products.*.units.*.scale_plu' => ['nullable', 'string', 'regex:/^\\d+$/', 'max:10'],
            'idempotency_key' => ['required', 'string', 'max:100'],
        ];
    }

    /** @param array<string, mixed> $schema @param array<string, mixed> $rules */
    private function makeDefinition( string $name, string $title, string $description, string $category, string|array $permission, array $schema, array $rules, string $risk = 'read', bool $requiresConfirmation = false ): OxenToolDefinition
    {
        return new OxenToolDefinition(
            name: $name,
            title: $title,
            description: $description,
            category: $category,
            sourceModule: 'NsOxen',
            inputSchema: $schema,
            outputSchema: ['type' => 'object', 'additionalProperties' => true],
            rules: $rules,
            ability: $risk === 'read' ? 'oxen:read' : ( $risk === 'destructive' ? 'oxen:destructive' : 'oxen:write' ),
            permissions: is_array( $permission ) ? $permission : [$permission],
            risk: $risk,
            requiresConfirmation: $requiresConfirmation,
        );
    }
}
