<?php

namespace App\Services;

use App\Classes\Currency;
use App\Classes\Hook;
use App\Jobs\EnsureCombinedProductHistoryExistsJob;
use App\Models\ActiveTransactionHistory;
use App\Models\Customer;
use App\Models\CustomerAccountHistory;
use App\Models\DashboardDay;
use App\Models\DashboardMonth;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductHistory;
use App\Models\ProductHistoryCombined;
use App\Models\ProductUnitQuantity;
use App\Models\Role;
use App\Models\TransactionAccount;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Modules\NsCommissions\Models\EarnedCommission;
use stdClass;

class ReportService
{
    private $dayStarts;

    private $dayEnds;

    public function __construct(
        protected DateService $dateService,
        protected ProductService $productService,
    ) {
        // ...
    }

    public function refreshFromDashboardDay( DashboardDay $todayReport )
    {
        $previousReport = DashboardDay::forLastRecentDay( $todayReport );

        /**
         * when the method is used without defining
         * the dayStarts and dayEnds, this method
         * create these values.
         */
        $this->defineDate( $todayReport );

        $this->computeUnpaidOrdersCount( $previousReport, $todayReport );
        $this->computeUnpaidOrders( $previousReport, $todayReport );
        $this->computePaidOrders( $previousReport, $todayReport );
        $this->computePaidOrdersCount( $previousReport, $todayReport );
        $this->computeOrdersTaxes( $previousReport, $todayReport );
        $this->computePartiallyPaidOrders( $previousReport, $todayReport );
        $this->computePartiallyPaidOrdersCount( $previousReport, $todayReport );
        $this->computeDiscounts( $previousReport, $todayReport );
        $this->computeIncome( $previousReport, $todayReport );
    }

    private function defineDate( DashboardDay $dashboardDay )
    {
        $this->dayStarts = empty( $this->dayStarts ) ? ( Carbon::parse( $dashboardDay->range_starts )->startOfDay()->toDateTimeString() ) : $this->dayStarts;
        $this->dayEnds = empty( $this->dayEnds ) ? ( Carbon::parse( $dashboardDay->range_ends )->endOfDay()->toDateTimeString() ) : $this->dayEnds;
    }

    /**
     * Will compute the report for the current day
     */
    public function computeDayReport( $dateStart = null, $dateEnd = null )
    {
        $this->dayStarts = $dateStart ?: $this->dateService->copy()->startOfDay()->toDateTimeString();
        $this->dayEnds = $dateEnd ?: $this->dateService->copy()->endOfDay()->toDateTimeString();

        $todayReport = DashboardDay::firstOrCreate( [
            'range_starts' => $this->dayStarts,
            'range_ends' => $this->dayEnds,
            'day_of_year' => Carbon::parse( $this->dayStarts )->dayOfYear,
        ] );

        $this->refreshFromDashboardDay( $todayReport );

        $todayReport->save();

        return $todayReport;
    }

    public function computeDashboardMonth( $todayCarbon = null )
    {
        if ( $todayCarbon === null ) {
            $todayCarbon = $this->dateService->copy()->now();
        }

        $monthStarts = $todayCarbon->startOfMonth()->toDateTimeString();
        $monthEnds = $todayCarbon->endOfMonth()->toDateTimeString();

        $entries = DashboardDay::from( $monthStarts )
            ->to( $monthEnds );

        $dashboardMonth = DashboardMonth::from( $monthStarts )
            ->to( $monthEnds )
            ->first();

        if ( ! $dashboardMonth instanceof DashboardMonth ) {
            $dashboardMonth = new DashboardMonth;
            $dashboardMonth->range_starts = $monthStarts;
            $dashboardMonth->range_ends = $monthEnds;
            $dashboardMonth->month_of_year = $todayCarbon->month;
            $dashboardMonth->save();
        }

        $dashboardMonth->month_unpaid_orders = $entries->sum( 'day_unpaid_orders' );
        $dashboardMonth->month_unpaid_orders_count = $entries->sum( 'day_unpaid_orders_count' );
        $dashboardMonth->month_paid_orders = $entries->sum( 'day_paid_orders' );
        $dashboardMonth->month_paid_orders_count = $entries->sum( 'day_paid_orders_count' );
        $dashboardMonth->month_partially_paid_orders = $entries->sum( 'day_partially_paid_orders' );
        $dashboardMonth->month_partially_paid_orders_count = $entries->sum( 'day_partially_paid_orders_count' );
        $dashboardMonth->month_income = $entries->sum( 'day_income' );
        $dashboardMonth->month_discounts = $entries->sum( 'day_discounts' );
        $dashboardMonth->month_taxes = $entries->sum( 'day_taxes' );
        $dashboardMonth->month_wasted_goods_count = $entries->sum( 'day_wasted_goods_count' );
        $dashboardMonth->month_wasted_goods = $entries->sum( 'day_wasted_goods' );
        $dashboardMonth->month_expenses = $entries->sum( 'day_expenses' );

        foreach ( [
            'total_unpaid_orders',
            'total_unpaid_orders_count',
            'total_paid_orders',
            'total_paid_orders_count',
            'total_partially_paid_orders',
            'total_partially_paid_orders_count',
            'total_income',
            'total_discounts',
            'total_taxes',
            'total_wasted_goods_count',
            'total_wasted_goods',
            'total_expenses',
        ] as $field ) {
            $dashboardMonth->$field = $entries->get()->last()->$field ?? 0;
        }

        $dashboardMonth->save();

        return $dashboardMonth;
    }

    public function computeOrdersTaxes( $previousReport, $todayReport )
    {
        $timeRangeTaxes = Order::from( $this->dayStarts )
            ->to( $this->dayEnds )
            ->paymentStatus( 'paid' )
            ->sum( 'tax_value' );

        $todayReport->day_taxes = $timeRangeTaxes;
        $todayReport->total_taxes = ( $todayReport->total_taxes ?? 0 ) + $timeRangeTaxes;
    }

    /**
     * will update wasted goods report
     *
     * @return void
     */
    public function handleStockAdjustment( ProductHistory $history )
    {
        if ( in_array( $history->operation_type, [
            ProductHistory::ACTION_DEFECTIVE,
            ProductHistory::ACTION_LOST,
            ProductHistory::ACTION_DELETED,
            ProductHistory::ACTION_REMOVED,
        ] ) ) {
            $currentDay = DashboardDay::forToday();

            if ( $currentDay instanceof DashboardDay ) {
                $yesterDay = DashboardDay::forLastRecentDay( $currentDay );
                $currentDay->day_wasted_goods_count += $history->quantity;
                $currentDay->day_wasted_goods += $history->total_price;
                $currentDay->total_wasted_goods_count = ( $yesterDay->total_wasted_goods_count ?? 0 ) + $currentDay->day_wasted_goods_count;
                $currentDay->total_wasted_goods = ( $yesterDay->total_wasted_goods ?? 0 ) + $currentDay->day_wasted_goods;
                $currentDay->save();

                return [
                    'status' => 'success',
                    'message' => __( 'The dashboard report has been updated.' ),
                ];
            }

            /**
             * @todo make sure outgoing link takes to relevant article
             *
             * @var NotificationService
             */
            $message = __( 'A stock operation has recently been detected, however NexoPOS was\'nt able to update the report accordingly. This occurs if the daily dashboard reference has\'nt been created.' );
            $notification = app()->make( NotificationService::class );
            $notification->create( [
                'title' => __( 'Untracked Stock Operation' ),
                'description' => $message,
                'url' => 'https://my.nexopos.com/en/troubleshooting/untracked-stock-operation',
            ] )->dispatchForGroup( Role::namespace( 'admin' ) );

            return [
                'status' => 'error',
                'message' => $message,
            ];
        }

        return [
            'status' => 'error',
            'message' => __( 'Unsupported action' ),
        ];
    }

    public function computeIncome( $previousReport, $todayReport )
    {
        $totalIncome = ActiveTransactionHistory::from( $this->dayStarts )
            ->to( $this->dayEnds )
            ->operation( ActiveTransactionHistory::OPERATION_CREDIT )
            ->sum( 'value' );

        $totalExpenses = ActiveTransactionHistory::from( $this->dayStarts )
            ->to( $this->dayEnds )
            ->operation( ActiveTransactionHistory::OPERATION_DEBIT )
            ->sum( 'value' );

        $todayReport->day_expenses = $totalExpenses;
        $todayReport->day_income = $totalIncome;
        $todayReport->total_income = ( $previousReport->total_income ?? 0 ) + $todayReport->day_income;
        $todayReport->total_expenses = ( $previousReport->total_expenses ?? 0 ) + $todayReport->day_expenses;
    }

    /**
     * specifically compute
     * the unpaid orders count
     *
     * @return void
     */
    private function computeUnpaidOrdersCount( $previousReport, $todayReport )
    {
        $totalUnpaidOrders = Order::from( $this->dayStarts )
            ->to( $this->dayEnds )
            ->paymentStatus( 'unpaid' )
            ->count();

        $todayReport->day_unpaid_orders_count = $totalUnpaidOrders;
        $todayReport->total_unpaid_orders_count = ( $previousReport->total_unpaid_orders_count ?? 0 ) + $totalUnpaidOrders;
    }

    /**
     * specifically compute
     * the unpaid orders amount
     *
     * @return void
     */
    private function computeUnpaidOrders( $previousReport, $todayReport )
    {
        $totalUnpaidOrders = Order::from( $this->dayStarts )
            ->to( $this->dayEnds )
            ->paymentStatus( 'unpaid' )
            ->sum( 'total' );

        $todayReport->day_unpaid_orders = $totalUnpaidOrders;
        $todayReport->total_unpaid_orders = ( $previousReport->total_unpaid_orders ?? 0 ) + $totalUnpaidOrders;
    }

    /**
     * specifically compute
     * the aid orders amount
     *
     * @return void
     */
    private function computePaidOrders( $previousReport, $todayReport )
    {
        $totalPaid = Order::from( $this->dayStarts )
            ->to( $this->dayEnds )
            ->paymentStatus( 'paid' )
            ->sum( 'total' );

        $todayReport->day_paid_orders = $totalPaid;
        $todayReport->total_paid_orders = ( $previousReport->total_paid_orders ?? 0 ) + $totalPaid;
    }

    /**
     * specifically compute
     * the piad orders count
     *
     * @return void
     */
    private function computePaidOrdersCount( $previousReport, $todayReport )
    {
        $totalPaidOrders = Order::from( $this->dayStarts )
            ->to( $this->dayEnds )
            ->paymentStatus( 'paid' )
            ->count();

        $todayReport->day_paid_orders_count = $totalPaidOrders;
        $todayReport->total_paid_orders_count = ( $previousReport->total_paid_orders_count ?? 0 ) + $totalPaidOrders;
    }

    /**
     * specifically compute
     * the aid orders amount
     *
     * @return void
     */
    private function computePartiallyPaidOrders( $previousReport, $todayReport )
    {
        $totalPaid = Order::from( $this->dayStarts )
            ->to( $this->dayEnds )
            ->paymentStatus( 'partially_paid' )
            ->sum( 'total' );

        $todayReport->day_partially_paid_orders = $totalPaid;
        $todayReport->total_partially_paid_orders = ( $previousReport->total_partially_paid_orders ?? 0 ) + $totalPaid;
    }

    /**
     * specifically compute
     * the piad orders count
     *
     * @return void
     */
    private function computePartiallyPaidOrdersCount( $previousReport, $todayReport )
    {
        $totalPartiallyPaidOrdersCount = Order::from( $this->dayStarts )
            ->to( $this->dayEnds )
            ->paymentStatus( 'partially_paid' )
            ->count();

        $todayReport->day_partially_paid_orders_count = $totalPartiallyPaidOrdersCount;
        $todayReport->total_partially_paid_orders_count = ( $previousReport->total_partially_paid_orders_count ?? 0 ) + $totalPartiallyPaidOrdersCount;
    }

    private function computeDiscounts( $previousReport, $todayReport )
    {
        $totalDiscount = Order::from( $this->dayStarts )
            ->to( $this->dayEnds )
            ->paymentStatus( 'paid' )
            ->sum( 'discount' );

        $todayReport->day_discounts = $totalDiscount;
        $todayReport->total_discounts = ( $previousReport->total_discounts ?? 0 ) + $totalDiscount;
    }

    /**
     * @deprecated
     */
    public function increaseDailyExpenses( ActiveTransactionHistory $cashFlow, $today = null )
    {
        $today = $today === null ? DashboardDay::forToday() : $today;

        if ( $today instanceof DashboardDay ) {
            if ( $cashFlow->operation === ActiveTransactionHistory::OPERATION_DEBIT ) {
                $yesterday = DashboardDay::forLastRecentDay( $today );
                $today->day_expenses += $cashFlow->getRawOriginal( 'value' );
                $today->total_expenses = ( $yesterday->total_expenses ?? 0 ) + $today->day_expenses;
                $today->save();
            } else {
                $yesterday = DashboardDay::forLastRecentDay( $today );
                $today->day_income += $cashFlow->getRawOriginal( 'value' );
                $today->total_income = ( $yesterday->total_income ?? 0 ) + $today->day_income;
                $today->save();
            }

            return [
                'status' => 'success',
                'message' => __( 'The expense has been correctly saved.' ),
            ];
        }

        return $this->notifyIncorrectDashboardReport();
    }

    public function notifyIncorrectDashboardReport()
    {
        /**
         * @todo make sure outgoing link takes to relevant article
         *
         * @var NotificationService
         */
        $message = __( 'A stock operation has recently been detected, however NexoPOS was\'nt able to update the report accordingly. This occurs if the daily dashboard reference has\'nt been created.' );

        $notification = app()->make( NotificationService::class );
        $notification->create( [
            'title' => __( 'Untracked Stock Operation' ),
            'description' => $message,
            'url' => 'https://my.nexopos.com/en/troubleshooting/untracked-stock-operation',
        ] )->dispatchForGroup( Role::namespace( 'admin' ) );

        return [
            'status' => 'error',
            'message' => $message,
        ];
    }

    /**
     * get from a specific date
     *
     * @param  string     $startDate
     * @param  string     $endDate
     * @return Collection
     */
    public function getFromTimeRange( $startDate, $endDate )
    {
        return DashboardDay::from( $startDate )
            ->to( $endDate )
            ->get();
    }

    /**
     * This return the year report
     *
     * @return array $reports
     */
    public function getYearReportFor( int $year )
    {
        $date = $this->dateService->now();
        $date->year = $year >= 2019 && $year <= 2099 ? $year : 2020; // validate the date
        $startOfYear = $date->startOfYear()->copy();
        $endOfYear = $date->endOfYear()->copy();

        $reports = [];

        do {
            $currentMonth = $startOfYear->copy();

            $monthReport = DashboardMonth::from( $currentMonth->startOfMonth()->toDateTimeString() )
                ->to( $currentMonth->endOfMonth()->toDateTimeString() )
                ->first();

            if ( ! $monthReport instanceof DashboardMonth ) {
                $monthReport = $this->computeDashboardMonth( $currentMonth );
            }

            $reports[ (int) $currentMonth->format( 'm' ) ] = $monthReport;

            $startOfYear->addMonth();
        } while ( ! $startOfYear->isSameMonth( $endOfYear->copy()->addMonth() ) );

        return $reports;
    }

    /**
     * Will return the products report
     *
     * @param  string $startDate
     * @param  string $endDate
     * @param  string $sort
     * @return array
     */
    public function getProductSalesDiff( $startDate, $endDate, $sort )
    {
        $startDate = Carbon::parse( $startDate );
        $endDate = Carbon::parse( $endDate );
        $diffInDays = Carbon::parse( $startDate )->diffInDays( $endDate );

        if ( $diffInDays > 0 ) {
            // check if it's the start and end of the month
            $isStartOfMonth = Carbon::parse( $startDate )->startOfMonth()->isSameDay( $startDate );
            $isEndOfMonth = Carbon::parse( $endDate )->endOfMonth()->isSameDay( $endDate );

            if (
                $isStartOfMonth && $isEndOfMonth
            ) {
                $startCycle = Carbon::parse( $startDate )->subMonth()->startOfMonth();
                $endCycle = Carbon::parse( $endDate )->subDay()->subMonth()->endOfMonth();
            } else {
                $startCycle = Carbon::parse( $startDate )->subDays( $diffInDays + 1 );
                $endCycle = Carbon::parse( $endDate )->subDays( $diffInDays + 1 );
            }

            $previousDates = [
                'previous' => [
                    'startDate' => $startCycle->toDateTimeString(),
                    'endDate' => $endCycle->toDateTimeString(),
                ],
                'current' => [
                    'startDate' => $startDate->toDateTimeString(),
                    'endDate' => $endDate->toDateTimeString(),
                ],
            ];

            return $this->getBestRecords( $previousDates, $sort );
        } else {
            $startCycle = Carbon::parse( $startDate )->subDay();
            $endCycle = Carbon::parse( $endDate )->subDay();

            $previousDates = [
                'previous' => [
                    'startDate' => $startCycle->toDateTimeString(),
                    'endDate' => $endCycle->toDateTimeString(),
                ],
                'current' => [
                    'startDate' => $startDate->toDateTimeString(),
                    'endDate' => $endDate->toDateTimeString(),
                ],
            ];

            return $this->getBestRecords( $previousDates, $sort );
        }
    }

    /**
     * Will detect whether an increase
     * or decrease exists between an old and new value
     *
     * @param  int $old
     * @param  int $new
     * @return int
     */
    private function getDiff( $old, $new )
    {
        if ( $old > $new ) {
            return $this->computeDiff( $old, $new, 'decrease' );
        } else {
            return $this->computeDiff( $old, $new, 'increase' );
        }
    }

    /**
     * Will compute the difference between two numbers
     *
     * @param  int    $old
     * @param  int    $new
     * @param  string $operation
     * @return int
     */
    private function computeDiff( $old, $new, $operation )
    {
        if ( $new == 0 ) {
            return 100;
        } else {
            $change = ( ( $old - $new ) / $new ) * 100;

            return $operation === 'increase' ? abs( $change ) : $change;
        }
    }

    /**
     * Will proceed the request to the
     * database that returns the products report
     *
     * @param  array  $previousDates
     * @param  string $sort
     * @return void
     */
    private function getBestRecords( $previousDates, $sort )
    {
        $orderProductTable = Hook::filter( 'ns-model-table', 'nexopos_orders_products' );
        $orderTable = Hook::filter( 'ns-model-table', 'nexopos_orders' );

        switch ( $sort ) {
            case 'using_quantity_asc':
                $sorting = [
                    'column' => 'quantity',
                    'direction' => 'asc',
                ];
                break;
            case 'using_quantity_desc':
                $sorting = [
                    'column' => 'quantity',
                    'direction' => 'desc',
                ];
                break;
            case 'using_sales_asc':
                $sorting = [
                    'column' => 'total_price',
                    'direction' => 'asc',
                ];
                break;
            case 'using_sales_desc':
                $sorting = [
                    'column' => 'total_price',
                    'direction' => 'desc',
                ];
                break;
            case 'using_name_asc':
                $sorting = [
                    'column' => 'name',
                    'direction' => 'asc',
                ];
                break;
            case 'using_name_desc':
                $sorting = [
                    'column' => 'name',
                    'direction' => 'desc',
                ];
                break;
            default:
                $sorting = [
                    'column' => 'total_price',
                    'direction' => 'desc',
                ];
                break;
        }

        foreach ( $previousDates as $key => $report ) {
            $previousDates[ $key ][ 'products' ] = DB::table( $orderProductTable )
                ->select( [
                    $orderProductTable . '.unit_name as unit_name',
                    $orderProductTable . '.product_id as product_id',
                    $orderProductTable . '.name as name',
                    DB::raw( 'SUM( quantity ) as quantity' ),
                    DB::raw( 'SUM( total_price ) as total_price' ),
                    DB::raw( 'SUM( ' . env( 'DB_PREFIX' ) . $orderProductTable . '.tax_value ) as tax_value' ),
                ] )
                ->groupBy(
                    $orderProductTable . '.unit_name',
                    $orderProductTable . '.product_id',
                    $orderProductTable . '.name'
                )
                ->orderBy( $sorting[ 'column' ], $sorting[ 'direction' ] )
                ->join( $orderTable, $orderTable . '.id', '=', $orderProductTable . '.order_id' )
                ->where( $orderTable . '.created_at', '>=', $report[ 'startDate' ] )
                ->where( $orderTable . '.created_at', '<=', $report[ 'endDate' ] )
                ->whereIn( $orderTable . '.payment_status', [ Order::PAYMENT_PAID ] )
                ->get()
                ->map( function ( $product ) {
                    $product->difference = 0;

                    return $product;
                } );
        }

        foreach ( $previousDates[ 'current' ][ 'products' ] as $id => &$product ) {
            $default = new stdClass;
            $default->total_price = 0;
            $default->quantity = 0;

            $oldProduct = collect( $previousDates[ 'previous' ][ 'products' ] )->filter( function ( $previousProduct ) use ( $product ) {
                return $previousProduct->product_id === $product->product_id;
            } )->first() ?: $default;

            $product->old_total_price = $oldProduct->total_price;
            $product->old_quantity = $oldProduct->quantity ?? 0;
            $product->difference = $oldProduct->total_price > 0 ? $this->getDiff(
                $oldProduct->total_price,
                $product->total_price
            ) : 100;

            $product->evolution = $product->quantity > $oldProduct->quantity ? 'progress' : 'regress';
        }

        $previousDates[ 'current' ][ 'total_price' ] = collect( $previousDates[ 'current' ][ 'products' ] )
            ->map( fn( $product ) => $product->total_price )
            ->sum();

        $previousDates[ 'previous' ][ 'total_price' ] = collect( $previousDates[ 'previous' ][ 'products' ] )
            ->map( fn( $product ) => $product->total_price )
            ->sum();

        return $previousDates;
    }

    /**
     * Will return a report based
     * on the requested type.
     */
    public function getSaleReport( string $start, string $end, string $type, $user_id = null, $categories_id = null )
    {
        switch ( $type ) {
            case 'products_report':
                return $this->getProductsReports(
                    start: $start,
                    end: $end,
                    user_id: $user_id,
                    categories_id: $categories_id
                );
                break;
            case 'categories_report':
            case 'categories_summary':
                return $this->getCategoryReports(
                    start: $start,
                    end: $end,
                    user_id: $user_id,
                    categories_id: $categories_id
                );
                break;
        }
    }

    private function getSalesSummary( $orders )
    {
        $allSales = $orders->map( function ( $order ) {
            $productTaxes = $order->products()->sum( 'tax_value' );
            $totalPurchasePrice = $order->products()->sum( 'total_purchase_price' );

            return [
                'subtotal' => $order->subtotal,
                'product_taxes' => $productTaxes,
                'sales_discounts' => $order->discount,
                'sales_taxes' => $order->tax_value,
                'shipping' => $order->shipping,
                'total' => $order->total,
                'total_purchase_price' => $totalPurchasePrice,
                'profit' => ns()->currency->define( $order->total )
                    ->subtractBy( $totalPurchasePrice )
                    ->subtractBy( $order->tax_value )
                    ->subtractBy( $productTaxes )
                    ->toFloat(),
            ];
        } );

        return [
            'sales_discounts' => Currency::define( $allSales->sum( 'sales_discounts' ) )->toFloat(),
            'product_taxes' => Currency::define( $allSales->sum( 'product_taxes' ) )->toFloat(),
            'sales_taxes' => Currency::define( $allSales->sum( 'sales_taxes' ) )->toFloat(),
            'subtotal' => Currency::define( $allSales->sum( 'subtotal' ) )->toFloat(),
            'shipping' => Currency::define( $allSales->sum( 'shipping' ) )->toFloat(),
            'profit' => Currency::define( $allSales->sum( 'profit' ) )->toFloat(),
            'total_purchase_price' => Currency::define( $allSales->sum( 'total_purchase_price' ) )->toFloat(),
            'total' => Currency::define( $allSales->sum( 'total' ) )->toFloat(),
        ];
    }

    /**
     * @todo add support for category filter
     */
    public function getProductsReports( $start, $end, $user_id = null, $categories_id = null )
    {
        $request = Order::paymentStatus( Order::PAYMENT_PAID )
            ->from( $start )
            ->to( $end );

        if ( ! empty( $user_id ) ) {
            $request = $request->where( 'author', $user_id );
        }

        if ( ! empty( $categories_id ) ) {
            /**
             * Will only pull orders that has products which
             * belongs to the categories id provided
             */
            $request = $request->whereHas( 'products', function ( $query ) use ( $categories_id ) {
                $query->whereIn( 'product_category_id', $categories_id );
            } );

            /**
             * Will only pull products that belongs to the categories id provided.
             */
            $request = $request->with( [
                'products' => function ( $query ) use ( $categories_id ) {
                    $query->whereIn( 'product_category_id', $categories_id );
                },
            ] );
        }

        $orders = $request->get();
        $summary = $this->getSalesSummary( $orders );
        $products = $orders->map( fn( $order ) => $order->products )->flatten();
        $productsIds = $products->map( fn( $product ) => $product->product_id )->unique();

        return [
            'result' => $productsIds->map( function ( $id ) use ( $products ) {
                $product = $products->where( 'product_id', $id )->first();
                $filtredProdcuts = $products->where( 'product_id', $id )->all();

                $summable = [ 'quantity', 'discount', 'wholesale_tax_value', 'sale_tax_value', 'tax_value', 'total_price_without_tax', 'total_price', 'total_price_with_tax', 'total_purchase_price' ];
                foreach ( $summable as $key ) {
                    $product->$key = collect( $filtredProdcuts )->sum( $key );
                }

                return $product;
            } )->values(),
            'summary' => $summary,
        ];
    }

    public function getCategoryReports( $start, $end, $orderAttribute = 'name', $orderDirection = 'desc', $user_id = null, $categories_id = [] )
    {
        $request = Order::paymentStatus( Order::PAYMENT_PAID )
            ->from( $start )
            ->to( $end );

        $request->with( 'products' );

        if ( ! empty( $user_id ) ) {
            $request = $request->where( 'author', $user_id );
        }

        if ( ! empty( $categories_id ) ) {
            /**
             * Will only pull orders that has products which
             * belongs to the categories id provided
             */
            $request = $request->whereHas( 'products', function ( $query ) use ( $categories_id ) {
                $query->whereIn( 'product_category_id', $categories_id );
            } );

            /**
             * Will only pull products that belongs to the categories id provided.
             */
            $request = $request->with( [
                'products' => function ( $query ) use ( $categories_id ) {
                    $query->whereIn( 'product_category_id', $categories_id );
                },
            ] );
        }

        $orders = $request->get();

        /**
         * We'll pull the sales
         * summary
         */
        $summary = $this->getSalesSummary( $orders );

        $products = $orders->map( fn( $order ) => $order->products )->flatten();
        $category_ids = $orders->map( fn( $order ) => $order->products->map( fn( $product ) => $product->product_category_id ) );

        $unitIds = $category_ids->flatten()->unique()->toArray();

        /**
         * We'll get all category that are listed
         * on the product sold
         */
        $categories = ProductCategory::whereIn( 'id', $unitIds )
            ->orderBy( $orderAttribute, $orderDirection )
            ->get();

        /**
         * That will sum all the total prices
         */
        $result = $categories->map( function ( $category ) use ( $products ) {
            $categoryWithProducts = [...$category->toArray()];

            $rawProducts = collect( $products->where( 'product_category_id', $category->id )->all() )->values();
            $mergedProducts = [];

            /**
             * this will merge similar products
             * to summarize them.
             */
            $rawProducts->each( function ( $product ) use ( &$mergedProducts ) {
                if ( isset( $mergedProducts[ $product->product_id ] ) ) {
                    $mergedProducts[ $product->product_id ][ 'quantity' ] += $product->quantity;
                    $mergedProducts[ $product->product_id ][ 'tax_value' ] += $product->tax_value;
                    $mergedProducts[ $product->product_id ][ 'discount' ] += $product->discount;
                    $mergedProducts[ $product->product_id ][ 'total_price' ] += $product->total_price;
                    $mergedProducts[ $product->product_id ][ 'total_purchase_price' ] += $product->total_purchase_price;
                } else {
                    $mergedProducts[ $product->product_id ] = array_merge( $product->toArray(), [
                        'quantity' => $product->quantity,
                        'tax_value' => $product->tax_value,
                        'discount' => $product->discount,
                        'total_price' => $product->total_price,
                        'total_purchase_price' => $product->total_purchase_price,
                        'name' => $product->name,
                        'product_id' => $product->product_id,
                        'unit_id' => $product->unit_id,
                    ] );
                }
            } );

            $categoryWithProducts[ 'products' ] = array_values( $mergedProducts );
            $categoryWithProducts[ 'total_tax_value' ] = collect( $mergedProducts )->sum( 'tax_value' );
            $categoryWithProducts[ 'total_price' ] = collect( $mergedProducts )->sum( 'total_price' );
            $categoryWithProducts[ 'total_discount' ] = collect( $mergedProducts )->sum( 'discount' );
            $categoryWithProducts[ 'total_sold_items' ] = collect( $mergedProducts )->sum( 'quantity' );
            $categoryWithProducts[ 'total_purchase_price' ] = collect( $mergedProducts )->sum( 'total_purchase_price' );

            return $categoryWithProducts;
        } );

        return compact( 'result', 'summary' );
    }

    /**
     * Will returns the details for a specific cashier
     */
    public function getCashierDashboard( $cashier, $startDate = null, $endDate = null )
    {
        $cacheKey = 'cashier-report-' . $cashier;

        if ( ! empty( request()->query( 'refresh' ) ) ) {
            Cache::forget( $cacheKey );
        }

        return Cache::remember( $cacheKey, now()->addDay( 1 ), function () use ( $startDate, $cashier, $endDate ) {
            $startDate = $startDate === null ? ns()->date->getNow()->startOfDay()->toDateTimeString() : $startDate;
            $endDate = $endDate === null ? ns()->date->getNow()->endOfDay()->toDateTimeString() : $endDate;

            $totalSales = Order::paid()
                ->where( 'author', $cashier )
                ->count();

            $todaySales = Order::paid()
                ->where( 'created_at', '>=', $startDate )
                ->where( 'created_at', '<=', $endDate )
                ->where( 'author', $cashier )
                ->count();

            $totalSalesAmount = Order::paid()
                ->where( 'author', $cashier )
                ->sum( 'total' );

            $todaySalesAmount = Order::paid()
                ->where( 'created_at', '>=', $startDate )
                ->where( 'created_at', '<=', $endDate )
                ->where( 'author', $cashier )
                ->sum( 'total' );

            $totalRefundsAmount = Order::refunded()
                ->where( 'author', $cashier )
                ->sum( 'total' );

            $todayRefunds = Order::refunded()
                ->where( 'created_at', '>=', $startDate )
                ->where( 'created_at', '<=', $endDate )
                ->where( 'author', $cashier )
                ->sum( 'total' );

            $totalCustomers = Customer::where( 'author', $cashier )
                ->count();

            $todayCustomers = Customer::where( 'created_at', '>=', $startDate )
                ->where( 'created_at', '<=', $endDate )
                ->where( 'author', $cashier )
                ->count();

            /**
             * This will compute the cashier
             * commissions and displays on his dashboard.
             */
            $module = app()->make( ModulesService::class );
            $config = [];

            if ( $module->getIfEnabled( 'NsCommissions' ) ) {
                $config = [
                    'today_commissions' => EarnedCommission::for( Auth::id() )
                        ->where( 'created_at', '>=', ns()->date->getNow()->copy()->startOfDay()->toDateTimeString() )
                        ->where( 'created_at', '<=', ns()->date->getNow()->copy()->endOfDay()->toDateTimeString() )
                        ->sum( 'value' ),
                    'total_commissions' => EarnedCommission::for( Auth::id() )
                        ->sum( 'value' ),
                ];
            }

            return array_merge( [
                [
                    'key' => 'created_at',
                    'value' => ns()->date->getFormatted( Auth::user()->created_at ),
                    'label' => __( 'Member Since' ),
                ], [
                    'key' => 'total_sales_count',
                    'value' => $totalSales,
                    'label' => __( 'Total Orders' ),
                    'today' => [
                        'key' => 'today_sales_count',
                        'value' => $todaySales,
                        'label' => __( 'Today\'s Orders' ),
                    ],
                ], [
                    'key' => 'total_sales_amount',
                    'value' => ns()->currency->define( $totalSalesAmount )->format(),
                    'label' => __( 'Total Sales' ),
                    'today' => [
                        'key' => 'today_sales_amount',
                        'value' => ns()->currency->define( $todaySalesAmount )->format(),
                        'label' => __( 'Today\'s Sales' ),
                    ],
                ], [
                    'key' => 'total_refunds_amount',
                    'value' => ns()->currency->define( $totalRefundsAmount )->format(),
                    'label' => __( 'Total Refunds' ),
                    'today' => [
                        'key' => 'today_refunds_amount',
                        'value' => ns()->currency->define( $todayRefunds )->format(),
                        'label' => __( 'Today\'s Refunds' ),
                    ],
                ], [
                    'key' => 'total_customers',
                    'value' => $totalCustomers,
                    'label' => __( 'Total Customers' ),
                    'today' => [
                        'key' => 'today_customers',
                        'value' => $todayCustomers,
                        'label' => __( 'Today\'s Customers' ),
                    ],
                ],
            ], $config );
        } );
    }

    /**
     * @param  int   $year
     * @return array $response
     */
    public function computeYearReport( $year )
    {
        $date = ns()->date->copy();
        $date->year = $year;
        $startOfYear = $date->copy()->startOfYear();
        $endOfYear = $date->copy()->endOfYear();

        while ( ! $startOfYear->isSameMonth( $endOfYear ) ) {
            $this->computeDashboardMonth( $startOfYear->copy() );
            $startOfYear->addMonth();
        }

        return [
            'status' => 'success',
            'message' => __( 'The report has been computed successfully.' ),
        ];
    }

    public function getStockReport( $categories, $units )
    {
        $query = Product::notGrouped()
            ->withStockEnabled()
            ->with( [ 'unit_quantities' => function ( $query ) use ( $units ) {
                if ( ! empty( $units ) ) {
                    $query->whereIn( 'unit_id', $units );
                } else {
                    return false;
                }
            }, 'unit_quantities.unit' ] );

        if ( ! empty( $categories ) ) {
            $query->whereIn( 'category_id', $categories );
        }

        return $query->paginate( 50 );
    }

    /**
     * Will retrun products having low stock
     *
     * @return array $products
     */
    public function getLowStockProducts( $categories, $units )
    {
        return ProductUnitQuantity::query()
            ->where( 'stock_alert_enabled', 1 )
            ->whereRaw( 'low_quantity > quantity' )
            ->with( [
                'product',
                'unit' => function ( $query ) use ( $units ) {
                    if ( ! empty( $units ) ) {
                        $query->whereIn( 'id', $units );
                    }
                },
            ] )
            ->whereHas( 'unit', function ( $query ) use ( $units ) {
                if ( ! empty( $units ) ) {
                    $query->whereIn( 'id', $units );
                } else {
                    return false;
                }
            } )
            ->whereHas( 'product', function ( $query ) use ( $categories ) {
                if ( ! empty( $categories ) ) {
                    $query->whereIn( 'category_id', $categories );
                }

                return false;
            } )
            ->get();
    }

    public function recomputeTransactions( $fromDate, $toDate )
    {
        ActiveTransactionHistory::truncate();
        DashboardDay::truncate();
        DashboardMonth::truncate();

        $startDateString = $fromDate->startOfDay()->toDateTimeString();
        $endDateString = $toDate->endOfDay()->toDateTimeString();

        /**
         * @var TransactionService
         */
        $transactionService = app()->make( TransactionService::class );

        $transactionService->recomputeCashFlow(
            $startDateString,
            $endDateString
        );

        $days = ns()->date->getDaysInBetween( $fromDate, $toDate );

        foreach ( $days as $day ) {
            $this->computeDayReport(
                $day->startOfDay()->toDateTimeString(),
                $day->endOfDay()->toDateTimeString()
            );

            $this->computeDashboardMonth( $day );
        }
    }

    /**
     * Will return the actual customer statement
     *
     * @return array
     */
    public function getCustomerStatement( Customer $customer, $rangeStarts = null, $rangeEnds = null )
    {
        $rangeStarts = Carbon::parse( $rangeStarts )->toDateTimeString();
        $rangeEnds = Carbon::parse( $rangeEnds )->toDateTimeString();

        return [
            'purchases_amount' => $customer->purchases_amount,
            'owed_amount' => $customer->owed_amount,
            'account_amount' => $customer->account_amount,
            'total_orders' => $customer->orders()->count(),
            'credit_limit_amount' => $customer->credit_limit_amount,
            'orders' => Order::where( 'customer_id', $customer->id )
                ->paymentStatusIn( [ Order::PAYMENT_PAID, Order::PAYMENT_UNPAID, Order::PAYMENT_REFUNDED, Order::PAYMENT_PARTIALLY ] )
                ->where( 'created_at', '>=', $rangeStarts )
                ->where( 'created_at', '<=', $rangeEnds )
                ->get(),
            'wallet_transactions' => CustomerAccountHistory::where( 'customer_id', $customer->id )
                ->where( 'created_at', '>=', $rangeStarts )
                ->where( 'created_at', '<=', $rangeEnds )
                ->get(),
        ];
    }

    public function combineProductHistory( ProductHistory $productHistory )
    {
        $currentDetailedHistory = $this->prepareProductHistoryCombinedHistory( $productHistory );
        $this->saveProductHistoryCombined( $currentDetailedHistory, $productHistory );
        $currentDetailedHistory->save();
    }

    /**
     * Will compute the product history combined for the whole day
     */
    public function computeProductHistoryCombinedForWholeDay( ProductHistory $productHistory ): ProductHistoryCombined
    {
        $startOfDay = Carbon::parse( $productHistory->created_at )->startOfDay();
        $endOfDay = Carbon::parse( $productHistory->created_at )->endOfDay();

        $initialQuantity = 0;

        $previousProductHistory = ProductHistoryCombined::where( 'date', '<', $startOfDay->toDateTimeString() )
            ->where( 'product_id', $productHistory->product_id )
            ->where( 'unit_id', $productHistory->unit_id )
            ->orderBy( 'date', 'desc' )
            ->first();

        if ( $previousProductHistory ) {
            $initialQuantity = $previousProductHistory->final_quantity;
        }

        $addedQuantity = ProductHistory::where( 'operation_type', ProductHistory::ACTION_ADDED )
            ->where( 'product_id', $productHistory->product_id )
            ->where( 'unit_id', $productHistory->unit_id )
            ->where( 'created_at', '>=', $startOfDay->toDateTimeString() )
            ->where( 'created_at', '<=', $endOfDay->toDateTimeString() )
            ->sum( 'quantity' );

        $defectiveQuantity = ProductHistory::where( 'operation_type', ProductHistory::ACTION_DEFECTIVE )
            ->where( 'product_id', $productHistory->product_id )
            ->where( 'unit_id', $productHistory->unit_id )
            ->where( 'created_at', '>=', $startOfDay->toDateTimeString() )
            ->where( 'created_at', '<=', $endOfDay->toDateTimeString() )
            ->sum( 'quantity' );

        $soldQuantity = ProductHistory::where( 'operation_type', ProductHistory::ACTION_SOLD )
            ->where( 'product_id', $productHistory->product_id )
            ->where( 'unit_id', $productHistory->unit_id )
            ->where( 'created_at', '>=', $startOfDay->toDateTimeString() )
            ->where( 'created_at', '<=', $endOfDay->toDateTimeString() )
            ->sum( 'quantity' );

        $finalQuantity = $initialQuantity + $addedQuantity - $defectiveQuantity - $soldQuantity;

        $productHistoryCombined = ProductHistoryCombined::where( 'date', $startOfDay->format( 'Y-m-d' ) )
            ->where( 'product_id', $productHistory->product_id )
            ->where( 'unit_id', $productHistory->unit_id )
            ->firstOrNew();

        $productHistoryCombined->final_quantity = $finalQuantity;
        $productHistoryCombined->initial_quantity = $initialQuantity;
        $productHistoryCombined->procured_quantity = $addedQuantity;
        $productHistoryCombined->defective_quantity = $defectiveQuantity;
        $productHistoryCombined->sold_quantity = $soldQuantity;
        $productHistoryCombined->product_id = $productHistory->product_id;
        $productHistoryCombined->unit_id = $productHistory->unit_id;
        $productHistoryCombined->name = $productHistory->product->name;
        $productHistoryCombined->date = $startOfDay->format( 'Y-m-d' );
        $productHistoryCombined->save();

        return $productHistoryCombined;
    }

    /**
     * Will prepare the product history combined
     */
    public function prepareProductHistoryCombinedHistory( ProductHistory $productHistory ): ProductHistoryCombined
    {
        $formatedDate = $this->dateService->now()->format( 'Y-m-d' );
        $currentDetailedHistory = ProductHistoryCombined::where( 'date', $formatedDate )
            ->where( 'unit_id', $productHistory->unit_id )
            ->where( 'product_id', $productHistory->product_id )
            ->first();

        /**
         * if this is not set, the we're probably doing this for the
         * first time of the day, so we need to pull the current quantity of the product
         */
        if ( ! $currentDetailedHistory instanceof ProductHistoryCombined ) {
            $currentDetailedHistory = new ProductHistoryCombined;
            $currentDetailedHistory->date = $formatedDate;
            $currentDetailedHistory->name = $productHistory->product->name;
            $currentDetailedHistory->initial_quantity = $productHistory->before_quantity ?? 0;
            $currentDetailedHistory->procured_quantity = 0;
            $currentDetailedHistory->sold_quantity = 0;
            $currentDetailedHistory->defective_quantity = 0;
            $currentDetailedHistory->final_quantity = 0;
            $currentDetailedHistory->product_id = $productHistory->product_id;
            $currentDetailedHistory->unit_id = $productHistory->unit_id;
        }

        return $currentDetailedHistory;
    }

    /**
     * Will save the product history combined
     */
    public function saveProductHistoryCombined( ProductHistoryCombined &$currentDetailedHistory, ProductHistory $productHistory ): ProductHistoryCombined
    {
        if ( $productHistory->operation_type === ProductHistory::ACTION_ADDED ) {
            $currentDetailedHistory->procured_quantity += $productHistory->quantity;
        } elseif ( $productHistory->operation_type === ProductHistory::ACTION_DELETED ) {
            $currentDetailedHistory->defective_quantity += $productHistory->quantity;
        } elseif ( $productHistory->operation_type === ProductHistory::ACTION_STOCKED ) {
            $currentDetailedHistory->procured_quantity += $productHistory->quantity;
        } elseif ( $productHistory->operation_type === ProductHistory::ACTION_LOST ) {
            $currentDetailedHistory->defective_quantity += $productHistory->quantity;
        } elseif ( $productHistory->operation_type === ProductHistory::ACTION_REMOVED ) {
            $currentDetailedHistory->defective_quantity += $productHistory->quantity;
        } elseif ( $productHistory->operation_type === ProductHistory::ACTION_SOLD ) {
            $currentDetailedHistory->sold_quantity += $productHistory->quantity;
        } elseif ( $productHistory->operation_type === ProductHistory::ACTION_ADJUSTMENT_RETURN ) {
            $currentDetailedHistory->procured_quantity += $productHistory->quantity;
        } elseif ( $productHistory->operation_type === ProductHistory::ACTION_CONVERT_IN ) {
            $currentDetailedHistory->procured_quantity += $productHistory->quantity;
        } elseif ( $productHistory->operation_type === ProductHistory::ACTION_RETURNED ) {
            $currentDetailedHistory->procured_quantity += $productHistory->quantity;
        } elseif ( $productHistory->operation_type === ProductHistory::ACTION_TRANSFER_IN ) {
            $currentDetailedHistory->procured_quantity += $productHistory->quantity;
        } elseif ( $productHistory->operation_type === ProductHistory::ACTION_TRANSFER_CANCELED ) {
            $currentDetailedHistory->procured_quantity += $productHistory->quantity;
        } elseif ( $productHistory->operation_type === ProductHistory::ACTION_TRANSFER_REJECTED ) {
            $currentDetailedHistory->procured_quantity += $productHistory->quantity;
        }

        $currentDetailedHistory->final_quantity = ns()->currency->define( $currentDetailedHistory->initial_quantity )
            ->additionateBy( $currentDetailedHistory->procured_quantity )
            ->subtractBy( $currentDetailedHistory->sold_quantity )
            ->subtractBy( $currentDetailedHistory->defective_quantity )
            ->toFloat();

        return $currentDetailedHistory;
    }

    public function getCombinedProductHistory( $date, $categories, $units )
    {
        $request = DB::query()->select( [
            'nexopos_products_unit_quantities.*',
            'nexopos_products.category_id as product_category_id',
            'nexopos_units.name as unit_name',
            'nexopos_products_histories_combined.date as history_date',
            'nexopos_products_histories_combined.initial_quantity as history_initial_quantity',
            'nexopos_products_histories_combined.procured_quantity as history_procured_quantity',
            'nexopos_products_histories_combined.defective_quantity as history_defective_quantity',
            'nexopos_products_histories_combined.sold_quantity as history_sold_quantity',
            'nexopos_products_histories_combined.final_quantity as history_final_quantity',
            'nexopos_products_histories_combined.unit_id as history_unit_id',
            'nexopos_products_histories_combined.product_id as history_product_id',
            'nexopos_products_histories_combined.name as history_name',
        ] )->from( 'nexopos_products_unit_quantities' )
            ->rightJoin( 'nexopos_products', 'nexopos_products.id', '=', 'nexopos_products_unit_quantities.product_id' )
            ->rightJoin( 'nexopos_products_histories_combined', function ( $join ) use ( $date ) {
                $join->on( 'nexopos_products_histories_combined.product_id', '=', 'nexopos_products_unit_quantities.product_id' );
                $join->on( 'nexopos_products_histories_combined.unit_id', '=', 'nexopos_products_unit_quantities.unit_id' );
                $join->where( 'nexopos_products_histories_combined.date', $date );
            } )
            ->rightJoin( 'nexopos_units', 'nexopos_units.id', '=', 'nexopos_products_histories_combined.unit_id' );

        /**
         * Will only pull products that belongs to the units id provided.
         */
        if ( ! empty( $units ) ) {
            $request->whereIn( 'nexopos_products_histories_combined.unit_id', $units );
        }

        if ( ! empty( $categories ) ) {
            $request->whereIn( 'nexopos_products.category_id', $categories );
        }

        $request->where( 'nexopos_products_histories_combined.date', Carbon::parse( $date )->format( 'Y-m-d' ) );

        return $request->get();
    }

    /**
     * Only trigger the job for combined products.
     */
    public function computeCombinedReport( $date )
    {
        EnsureCombinedProductHistoryExistsJob::dispatch( $date );

        return [
            'status' => 'success',
            'message' => __( 'The report will be generated. Try loading the report within few minutes.' ),
        ];
    }

    public function getAccountSummaryReport( $startDate = null, $endDate = null )
    {
        $startDate = $startDate === null ? ns()->date->getNow()->startOfMonth()->toDateTimeString() : $startDate;
        $endDate = $endDate === null ? ns()->date->getNow()->endOfMonth()->toDateTimeString() : $endDate;
        $accounts = collect( config( 'accounting.accounts' ) )->map( function ( $account, $name ) use ( $startDate, $endDate ) {
            $transactionAccount = TransactionAccount::where( 'category_identifier', $name )->with( [ 'histories' => function ( $query ) use ( $startDate, $endDate ) {
                $query->where( 'created_at', '>=', $startDate )->where( 'created_at', '<=', $endDate );
            }] )->get();

            $transactions = $transactionAccount->map( function ( $account ) {
                return [
                    'name' => $account->name,
                    'debits' => $account->histories->where( 'operation', 'debit' )->sum( 'value' ),
                    'credits' => $account->histories->where( 'operation', 'credit' )->sum( 'value' ),
                ];
            } );

            return [
                'transactions' => $transactions,
                'name' => $account[ 'label' ](),
                'debits' => $transactions->sum( 'debits' ),
                'credits' => $transactions->sum( 'credits' ),
            ];
        } );

        return [
            'accounts' => $accounts,
            'debits' => $accounts->sum( 'debits' ),
            'credits' => $accounts->sum( 'credits' ),
            'profit' => ns()->currency->define( $accounts->sum( 'credits' ) )->subtractBy( $accounts->sum( 'debits' ) )->toFloat(),
        ];
    }
}
