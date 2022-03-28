<?php
namespace App\Services;

use App\Classes\Currency;
use App\Classes\Hook;
use App\Models\CashFlow;
use App\Models\Customer;
use App\Models\CustomerAccountHistory;
use App\Models\DashboardDay;
use App\Models\DashboardMonth;
use App\Models\Order;
use App\Models\OrderRefund;
use App\Models\Procurement;
use App\Models\ProductCategory;
use App\Models\ProductHistory;
use App\Models\ProductUnitQuantity;
use App\Models\RegisterHistory;
use App\Models\Role;
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
    private $dateService;

    public function __construct(
        DateService $dateService
    ) {
        $this->dateService  =   $dateService;
    }

    public function refreshFromDashboardDay( DashboardDay $todayReport )
    {
        $previousReport     =   DashboardDay::forLastRecentDay( $todayReport );
        
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
        $this->dayStarts      =   empty( $this->dayStarts ) ? ( Carbon::parse( $dashboardDay->range_starts )->startOfDay()->toDateTimeString() ) : $this->dayStarts;
        $this->dayEnds        =   empty( $this->dayEnds ) ? ( Carbon::parse( $dashboardDay->range_ends )->endOfDay()->toDateTimeString() ) : $this->dayEnds;
    }

    /**
     * Will compute the report for the current day
     */
    public function computeDayReport( $dateStart = null, $dateEnd = null )
    {
        $this->dayStarts      =   $dateStart ?: $this->dateService->copy()->startOfDay()->toDateTimeString();
        $this->dayEnds        =   $dateEnd ?: $this->dateService->copy()->endOfDay()->toDateTimeString();

        /**
         * Before proceeding, let's clear everything 
         * that is not assigned during this specific time range.
         */
        $this->clearUnassignedCashFlow( $this->dayStarts, $this->dayEnds );
        
        $todayReport        =   DashboardDay::from( $this->dayStarts )
            ->to( $this->dayEnds )
            ->first();

        if ( ! $todayReport instanceof DashboardDay ) {
            $todayReport                =   new DashboardDay;
            $todayReport->day_of_year   =   Carbon::parse( $this->dayStarts )->dayOfYear;
        }

        $todayReport->range_starts  =   $this->dayStarts;
        $todayReport->range_ends    =   $this->dayEnds;

        $this->refreshFromDashboardDay( $todayReport );

        $todayReport->save();

        return $todayReport;
    }

    public function computeDashboardMonth( $todayCarbon = null )
    {
        if ( $todayCarbon === null ) {
            $todayCarbon    =   $this->dateService->copy()->now();
        }

        $monthStarts    =   $todayCarbon->startOfMonth()->toDateTimeString();
        $monthEnds      =   $todayCarbon->endOfMonth()->toDateTimeString();

        $entries            =   DashboardDay::from( $monthStarts )
            ->to( $monthEnds )
            ->get();

        $dashboardMonth     =   DashboardMonth::from( $monthStarts )
            ->to( $monthEnds )
            ->first();

        if ( ! $dashboardMonth instanceof DashboardMonth ) {
            $dashboardMonth                 =   new DashboardMonth;
            $dashboardMonth->range_starts   =   $monthStarts;
            $dashboardMonth->range_ends     =   $monthEnds;
            $dashboardMonth->month_of_year  =   $todayCarbon->month;
            $dashboardMonth->save();
        }

        $dashboardMonth->month_unpaid_orders                    =   $entries->sum( 'day_unpaid_order' );
        $dashboardMonth->month_unpaid_orders_count              =   $entries->sum( 'day_unpaid_orders_count' );
        $dashboardMonth->month_paid_orders                      =   $entries->sum( 'day_paid_orders' );
        $dashboardMonth->month_paid_orders_count                =   $entries->sum( 'day_paid_orders_count' );
        $dashboardMonth->month_partially_paid_orders            =   $entries->sum( 'day_partially_paid_orders' );
        $dashboardMonth->month_partially_paid_orders_count      =   $entries->sum( 'day_partially_paid_orders_count' );
        $dashboardMonth->month_income                           =   $entries->sum( 'day_income' );
        $dashboardMonth->month_discounts                        =   $entries->sum( 'day_discounts' );
        $dashboardMonth->month_taxes                            =   $entries->sum( 'day_taxes' );
        $dashboardMonth->month_wasted_goods_count               =   $entries->sum( 'day_wasted_goods_count' );
        $dashboardMonth->month_wasted_goods                     =   $entries->sum( 'day_wasted_goods' );
        $dashboardMonth->month_expenses                         =   $entries->sum( 'day_expenses' );

        foreach([
            "total_unpaid_orders",
            "total_unpaid_orders_count",
            "total_paid_orders",
            "total_paid_orders_count",
            "total_partially_paid_orders",
            "total_partially_paid_orders_count",
            "total_income",
            "total_discounts",
            "total_taxes",
            "total_wasted_goods_count",
            "total_wasted_goods",
            "total_expenses",
        ] as $field ) {
            $dashboardMonth->$field     =   $entries->last()->$field ?? 0;
        }
        
        $dashboardMonth->save();

        return $dashboardMonth;
    }

    public function computeOrdersTaxes( $previousReport, $todayReport )
    {
        $timeRangeTaxes     =   Order::from( $this->dayStarts  )
            ->to( $this->dayEnds )
            ->paymentStatus( 'paid' )
            ->sum( 'tax_value' );

        $todayReport->day_taxes     =   $timeRangeTaxes;
        $todayReport->total_taxes   =   ( $todayReport->total_taxes ?? 0 ) + $timeRangeTaxes;
    }

    /**
     * will update wasted goods report
     * @param ProductHistory $history
     * @return void
     */
    public function handleStockAdjustment( ProductHistory $history )
    {
        if ( in_array( $history->operation_type, [
            ProductHistory::ACTION_DEFECTIVE,
            ProductHistory::ACTION_LOST,
            ProductHistory::ACTION_DELETED,
            ProductHistory::ACTION_REMOVED,
        ])) {
            $currentDay     =   DashboardDay::forToday();

            if ( $currentDay instanceof DashboardDay ) {
                $yesterDay                                  =   DashboardDay::forLastRecentDay( $currentDay );
                $currentDay->day_wasted_goods_count         +=   $history->quantity;
                $currentDay->day_wasted_goods               +=   $history->total_price;
                $currentDay->total_wasted_goods_count       =   ( $yesterDay->total_wasted_goods_count ?? 0 ) + $currentDay->day_wasted_goods_count;
                $currentDay->total_wasted_goods             =   ( $yesterDay->total_wasted_goods ?? 0 ) + $currentDay->day_wasted_goods;
                $currentDay->save();
    
                return [
                    'status'    =>  'success',
                    'message'   =>  __( 'The dashboard report has been updated.' )
                ];
            }
            
            /**
             * @todo make sure outgoing link takes to relevant article
             * @var NotificationService
             */
            $message            =   __( 'A stock operation has recently been detected, however the NexoPOS was\'nt able to update the report accordingly. This occurs if the daily dashboard reference has\'nt been created.' );
            $notification       =   app()->make( NotificationService::class );
            $notification->create([
                'title'         =>      __( 'Untracked Stock Operation' ),
                'description'   =>      $message,
                'url'           =>      'https://my.nexopos.com/en/troubleshooting/untracked-stock-operation'
            ])->dispatchForGroup( Role::namespace( 'admin' ) );

            return [
                'status'    =>  'failed',
                'message'   =>  $message
            ];
        }

        return [
            'status'    =>  'failed',
            'message'   =>  __( 'Unsupported action' ),
        ];
    }

    /**
     * Clear all orphan stock flow
     * to avoid inaccurate computing
     * @param string $startAt
     * @param string $endAt
     * @return void
     */
    public function clearUnassignedCashFlow( $startAt, $endsAt )
    {
        $cashFlows  =   CashFlow::where( 'created_at', '>=', $startAt )
            ->where( 'created_at', '<=', $endsAt )
            ->get();

        $cashFlows->each( function( $cashFlow ) {
            /**
             * let's clear unassigned to orders
             */
            if ( $cashFlow->operation === CashFlow::OPERATION_CREDIT && ! empty( $cashFlow->order_id ) ) {
                $order  =   Order::find( $cashFlow->order_id );

                if( ! $order instanceof Order ) {
                    $cashFlow->delete();
                }
            }

            /**
             * let's clear unassigned to procurements
             */
            if ( $cashFlow->operation === CashFlow::OPERATION_DEBIT && ! empty( $cashFlow->procurement_id ) ) {
                $order  =   Procurement::find( $cashFlow->procurement_id );

                if( ! $order instanceof Procurement ) {
                    $cashFlow->delete();
                }
            }

            /**
             * let's clear unassigned to order refund
             */
            if ( ! empty( $cashFlow->order_refund_id ) ) {
                $order  =   OrderRefund::find( $cashFlow->order_refund_id );

                if( ! $order instanceof OrderRefund ) {
                    $cashFlow->delete();
                }
            }

            /**
             * let's clear unassigned to register history
             */
            if ( ! empty( $cashFlow->register_history_id ) ) {
                $history  =   RegisterHistory::find( $cashFlow->register_history_id );

                if( ! $history instanceof RegisterHistory ) {
                    $cashFlow->delete();
                }
            }

            /**
             * let's clear unassigned to customer account history
             */
            if ( ! empty( $cashFlow->customer_account_history_id ) ) {
                $history  =   CustomerAccountHistory::find( $cashFlow->customer_account_history_id );

                if( ! $history instanceof CustomerAccountHistory ) {
                    $cashFlow->delete();
                }
            }
        });
    }

    /**
     * Will delete all cash flow
     * related to the specific order
     * @param Order $order
     * @return void
     */
    public function deleteOrderCashFlow( Order $order )
    {
        CashFlow::where( 'order_id', $order->id )->delete();
    }

    /**
     * Will delete all procurement
     * related to a specific cash flow
     * @param Procurement $procurement
     * @return void
     */
    public function deleteProcurementCashFlow( Procurement $procurement )
    {
        CashFlow::where( 'procurement_id', $procurement->id )->delete();
    }

    public function computeIncome( $previousReport, $todayReport )
    {
        $totalIncome         =   CashFlow::from( $this->dayStarts )
            ->to( $this->dayEnds )
            ->operation( CashFlow::OPERATION_CREDIT )
            ->sum( 'value' );

        $totalExpenses      =   CashFlow::from( $this->dayStarts )
            ->to( $this->dayEnds )
            ->operation( CashFlow::OPERATION_DEBIT )
            ->sum( 'value' );

        $todayReport->day_expenses      =   $totalExpenses;
        $todayReport->day_income        =   $totalIncome - $totalExpenses;
        $todayReport->total_income      =   ( $previousReport->total_income ?? 0 ) + $todayReport->day_income;
        $todayReport->total_expenses    =   ( $previousReport->total_expenses ?? 0 ) + $todayReport->day_expenses;
    }
    
    /**
     * specifically compute 
     * the unpaid orders count
     * @return void
     */
    private function computeUnpaidOrdersCount( $previousReport, $todayReport )
    {
        $totalUnpaidOrders         =   Order::from( $this->dayStarts )
            ->to( $this->dayEnds )
            ->paymentStatus( 'unpaid' )
            ->count();

        $todayReport->day_unpaid_orders_count     = $totalUnpaidOrders;
        $todayReport->total_unpaid_orders_count   = ( $previousReport->total_unpaid_orders_count ?? 0 ) + $totalUnpaidOrders;
    }

    /**
     * specifically compute 
     * the unpaid orders amount
     * @return void
     */
    private function computeUnpaidOrders( $previousReport, $todayReport )
    {
        $totalUnpaidOrders         =   Order::from( $this->dayStarts )
            ->to( $this->dayEnds )
            ->paymentStatus( 'unpaid' )
            ->sum( 'total' );

        $todayReport->day_unpaid_orders     = $totalUnpaidOrders;
        $todayReport->total_unpaid_orders   = ( $previousReport->total_unpaid_orders ?? 0 ) + $totalUnpaidOrders;
    }

    /**
     * specifically compute 
     * the aid orders amount
     * @return void
     */
    private function computePaidOrders( $previousReport, $todayReport )
    {
        $totalPaid  =   Order::from( $this->dayStarts )
            ->to( $this->dayEnds )
            ->paymentStatus( 'paid' )
            ->sum( 'total' );

        $todayReport->day_paid_orders     = $totalPaid;
        $todayReport->total_paid_orders   = ( $previousReport->total_paid_orders ?? 0 ) + $totalPaid;
    }

    /**
     * specifically compute 
     * the piad orders count
     * @return void
     */
    private function computePaidOrdersCount( $previousReport, $todayReport )
    {
        $totalPaidOrders         =   Order::from( $this->dayStarts )
            ->to( $this->dayEnds )
            ->paymentStatus( 'paid' )
            ->count();

        $todayReport->day_paid_orders_count     = $totalPaidOrders;
        $todayReport->total_paid_orders_count   = ( $previousReport->total_paid_orders_count ?? 0 ) + $totalPaidOrders;
    }

    /**
     * specifically compute 
     * the aid orders amount
     * @return void
     */
    private function computePartiallyPaidOrders( $previousReport, $todayReport )
    {
        $totalPaid  =   Order::from( $this->dayStarts )
            ->to( $this->dayEnds )
            ->paymentStatus( 'partially_paid' )
            ->sum( 'total' );

        $todayReport->day_partially_paid_orders     = $totalPaid;
        $todayReport->total_partially_paid_orders   = ( $previousReport->total_partially_paid_orders ?? 0 ) + $totalPaid;
    }

    /**
     * specifically compute 
     * the piad orders count
     * @return void
     */
    private function computePartiallyPaidOrdersCount( $previousReport, $todayReport )
    {
        $totalPartiallyPaidOrdersCount         =   Order::from( $this->dayStarts )
            ->to( $this->dayEnds )
            ->paymentStatus( 'partially_paid' )
            ->count();

        $todayReport->day_partially_paid_orders_count     = $totalPartiallyPaidOrdersCount;
        $todayReport->total_partially_paid_orders_count   = ( $previousReport->total_partially_paid_orders_count ?? 0 ) + $totalPartiallyPaidOrdersCount;
    }

    private function computeDiscounts( $previousReport, $todayReport )
    {
        $totalDiscount         =   Order::from( $this->dayStarts )
            ->to( $this->dayEnds )
            ->paymentStatus( 'paid' )
            ->sum( 'discount' );

        $todayReport->day_discounts     = $totalDiscount;
        $todayReport->total_discounts   = ( $previousReport->total_discounts ?? 0 ) + $totalDiscount;
    }

    public function increaseDailyExpenses( CashFlow $cashFlow, $today = null )
    {
        $today  =   $today === null ? DashboardDay::forToday() : $today;

        if ( $today instanceof DashboardDay ) {

            if ( $cashFlow->operation === CashFlow::OPERATION_DEBIT ) {
                $yesterday                  =   DashboardDay::forLastRecentDay( $today );
                $today->day_expenses        +=   $cashFlow->getRawOriginal( 'value' );
                $today->total_expenses      =   ( $yesterday->total_expenses ?? 0 ) + $today->day_expenses;
                $today->save();
            } else {
                $yesterday                  =   DashboardDay::forLastRecentDay( $today );
                $today->day_income          +=  $cashFlow->getRawOriginal( 'value' );
                $today->total_income        =   ( $yesterday->total_income ?? 0 ) + $today->day_income;
                $today->save();
            }

            return [
                'status'    =>  'success',
                'message'   =>  __( 'The expense has been correctly saved.' ),
            ];
        }

        return $this->notifyIncorrectDashboardReport();
    }

    public function reduceDailyExpenses( CashFlow $cashFlow, $today = null )
    {
        $today  =   $today === null ? DashboardDay::forToday() : $today;

        if ( $today instanceof DashboardDay ) {
            if ( $cashFlow->operation === CashFlow::OPERATION_CREDIT ) {
                $yesterday                  =   DashboardDay::forLastRecentDay( $today );
                $today->day_income          -=  $cashFlow->getRawOriginal( 'value' );
                $today->total_income        =   ( $yesterday->total_income ?? 0 ) + $today->day_income;
                $today->save();
            } else {
                $yesterday                  =   DashboardDay::forLastRecentDay( $today );
                $today->day_expenses        -=  $cashFlow->getRawOriginal( 'value' );
                $today->total_expenses      =   ( $yesterday->total_expenses ?? 0 ) + $today->day_expenses;
                $today->save();
            }

            return [
                'status'    =>  'success',
                'message'   =>  __( 'The expense has been correctly saved.' ),
            ];
        }

        return $this->notifyIncorrectDashboardReport();
    }

    public function notifyIncorrectDashboardReport()
    {
        /**
         * @todo make sure outgoing link takes to relevant article
         * @var NotificationService
         */
        $message            =   __( 'A stock operation has recently been detected, however the NexoPOS was\'nt able to update the report accordingly. This occurs if the daily dashboard reference has\'nt been created.' );

        $notification       =   app()->make( NotificationService::class );
        $notification->create([
            'title'         =>      __( 'Untracked Stock Operation' ),
            'description'   =>      $message,
            'url'           =>      'https://my.nexopos.com/en/troubleshooting/untracked-stock-operation'
        ])->dispatchForGroup( Role::namespace( 'admin' ) );

        return [
            'status'    =>  'failed',
            'message'   =>  $message
        ];
    }

    public function initializeDailyReport()
    {
        $dashboardDay   =   $this->computeDayReport();
        $this->initializeWastedGood( $dashboardDay );
    }

    /**
     * Will initialize a report for wasted good
     * @param DashboardDay $dashboardDay
     * @return void
     */
    public function initializeWastedGood( DashboardDay $dashboardDay )
    {
        $previousReport                                     =   DashboardDay::forLastRecentDay( $dashboardDay );
        $dashboardDay->total_unpaid_orders                  =   $previousReport->total_unpaid_orders ?? 0;
        $dashboardDay->day_unpaid_orders                    =   0;
        $dashboardDay->total_unpaid_orders_count            =   $previousReport->total_unpaid_orders_count ?? 0;
        $dashboardDay->day_unpaid_orders_count              =   0;
        $dashboardDay->total_paid_orders                    =   $previousReport->total_paid_orders ?? 0;
        $dashboardDay->day_paid_orders                      =   0;
        $dashboardDay->total_paid_orders_count              =   $previousReport->total_paid_orders_count ?? 0;
        $dashboardDay->day_paid_orders_count                =   0;
        $dashboardDay->total_partially_paid_orders          =   $previousReport->total_partially_paid_orders ?? 0;
        $dashboardDay->day_partially_paid_orders            =   0;
        $dashboardDay->total_partially_paid_orders_count    =   $previousReport->total_partially_paid_orders_count ?? 0;
        $dashboardDay->day_partially_paid_orders_count      =   0;
        $dashboardDay->total_income                         =   $previousReport->total_income ?? 0;
        $dashboardDay->day_income                           =   0;
        $dashboardDay->total_discounts                      =   $previousReport->total_discounts ?? 0;
        $dashboardDay->day_discounts                        =   0;
        $dashboardDay->total_wasted_goods_count             =   $previousReport->total_wasted_goods_count ?? 0;
        $dashboardDay->day_wasted_goods_count               =   0;
        $dashboardDay->total_wasted_goods                   =   $previousReport->total_wasted_goods ?? 0;
        $dashboardDay->day_wasted_goods                     =   0;
        $dashboardDay->total_expenses                       =   $previousReport->total_expenses ?? 0;
        $dashboardDay->day_expenses                         =   0;
        $dashboardDay->save();
    }

    /**
     * get from a specific date
     * @param string $startDate
     * @param string $endDate
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
     * @param string $year
     * @return array $reports
     */
    public function getYearReportFor( $year )
    {
        $date           =   $this->dateService->now();
        $date->year     =   $year >= 2019 && $year <= 2099 ? $year : 2020; // validate the date
        $startOfYear    =   $date->startOfYear()->copy();
        $endOfYear      =   $date->endOfYear()->copy();

        $reports        =   [];

        do {
            $currentMonth   =   $startOfYear->copy();

            $monthReport    =   DashboardMonth::from( $currentMonth->startOfMonth()->toDateTimeString() )
                ->to( $currentMonth->endOfMonth()->toDateTimeString() )
                ->first();

            if ( ! $monthReport instanceof DashboardMonth ) {
                $monthReport    =   $this->computeDashboardMonth( $currentMonth );
            }

            $reports[ ( int ) $currentMonth->format( 'm' ) ]       =   $monthReport;

            $startOfYear->addMonth();
        }
        while( ! $startOfYear->isSameMonth( $endOfYear->copy()->addMonth() ) );

        return $reports;
    }

    /**
     * Will return the products report
     * @param string $startDate
     * @param string $endDate
     * @param string $sort
     * @return array
     */
    public function getProductSalesDiff( $startDate, $endDate, $sort )
    {
        $startDate          =   Carbon::parse( $startDate );
        $endDate            =   Carbon::parse( $endDate ); 
        $diffInDays         =   Carbon::parse( $startDate )->diffInDays( $endDate );

        $orderProductTable  =   Hook::filter( 'ns-model-table', 'nexopos_orders_products' );
        $productsTable      =   Hook::filter( 'ns-model-table', 'nexopos_products' );
        $unitstable         =   Hook::filter( 'ns-model-table', 'nexopos_units' );

        if ( $diffInDays > 0 ) {
            // check if it's the start and end of the month
            $isStartOfMonth =   Carbon::parse( $startDate )->startOfMonth()->isSameDay( $startDate );
            $isEndOfMonth   =   Carbon::parse( $endDate )->endOfMonth()->isSameDay( $endDate );

            if ( 
                $isStartOfMonth && $isEndOfMonth
            ) {
                $startCycle     =   Carbon::parse( $startDate )->subMonth()->startOfMonth();
                $endCycle       =   Carbon::parse( $endDate )->subDay()->subMonth()->endOfMonth();
            } else {
                $startCycle     =   Carbon::parse( $startDate )->subDays( $diffInDays + 1 );
                $endCycle       =   Carbon::parse( $endDate )->subDays( $diffInDays + 1 );
            }

            $previousDates  =   [
                'previous'  =>  [
                    'startDate' =>  $startCycle->toDateTimeString(),
                    'endDate'   =>  $endCycle->toDateTimeString()
                ],
                'current'   =>  [
                    'startDate' =>  $startDate->toDateTimeString(),
                    'endDate'   =>  $endDate->toDateTimeString()
                ]
            ];

            return $this->getBestRecords( $previousDates, $sort );
        } else {
            $startCycle     =   Carbon::parse( $startDate )->subDay();
            $endCycle       =   Carbon::parse( $endDate )->subDay();

            $previousDates  =   [
                'previous'      =>  [
                    'startDate' =>  $startCycle->toDateTimeString(),
                    'endDate'   =>  $endCycle->toDateTimeString()
                ],
                'current'       =>  [
                    'startDate' =>  $startDate->toDateTimeString(),
                    'endDate'   =>  $endDate->toDateTimeString()
                ]
            ];

            return $this->getBestRecords( $previousDates, $sort );
        }
    }

    /**
     * Will detect wether an increase
     * or decrease exists between an old and new value
     * @param int $old
     * @param int $new
     * @return int
     */
    private function getDiff( $old, $new ) {
        if ( $old > $new ) {
            return $this->computeDiff( $old, $new, 'decrease' );
        } else {
            return $this->computeDiff( $old, $new, 'increase' );
        }
    }

    /**
     * Will compute the difference between two numbers
     * @param int $old
     * @param int $new
     * @param string $operation
     * @return int
     */
    private function computeDiff( $old, $new, $operation )
    {
        if ( $new == 0 ) {
            return 100;
        } else {
            $change     =   ( ( $old - $new ) / $new ) * 100;
            return $operation === 'increase' ? abs( $change ) : $change;
        }
    }

    /**
     * Will proceed the request to the 
     * database that returns the products report
     * @param array $previousDates
     * @param string $sort
     * @return void
     */
    private function getBestRecords( $previousDates, $sort )
    {
        $orderProductTable  =   Hook::filter( 'ns-model-table', 'nexopos_orders_products' );
        $orderTable         =   Hook::filter( 'ns-model-table', 'nexopos_orders' );
        $productsTable      =   Hook::filter( 'ns-model-table', 'nexopos_products' );
        $unitstable         =   Hook::filter( 'ns-model-table', 'nexopos_units' );
        
        switch( $sort ) {
            case 'using_quantity_asc': 
                $sorting    =   [
                    'column'    =>  'quantity',
                    'direction' =>  'asc'
                ];
            break;
            case 'using_quantity_desc': 
                $sorting    =   [
                    'column'    =>  'quantity',
                    'direction' =>  'desc'
                ];
            break;
            case 'using_sales_asc': 
                $sorting    =   [
                    'column'    =>  'total_price',
                    'direction' =>  'asc'
                ];
            break;
            case 'using_sales_desc': 
                $sorting    =   [
                    'column'    =>  'total_price',
                    'direction' =>  'desc'
                ];
            break;
            case 'using_name_asc': 
                $sorting    =   [
                    'column'    =>  'name',
                    'direction' =>  'asc'
                ];
            break;
            case 'using_name_desc': 
                $sorting    =   [
                    'column'    =>  'name',
                    'direction' =>  'desc'
                ];
            break;
            default: 
            $sorting    =   [
                'column'    =>  'total_price',
                'direction' =>  'desc'
            ];
            break;
        }

        foreach( $previousDates as $key => $report ) {
            $previousDates[ $key ][ 'products' ]    =   DB::table( $orderProductTable )
                ->select([
                    $orderProductTable . '.unit_name as unit_name',
                    $orderProductTable . '.product_id as product_id',
                    $orderProductTable . '.name as name',
                    DB::raw( 'SUM( quantity ) as quantity' ),
                    DB::raw( 'SUM( total_price ) as total_price' ),
                    DB::raw( 'SUM( ' . env( 'DB_PREFIX' ) . $orderProductTable . '.tax_value ) as tax_value' ),
                ])
                ->groupBy(          
                    $orderProductTable . '.unit_name', 
                    $orderProductTable . '.product_id', 
                    $orderProductTable . '.name'
                )
                ->orderBy( $sorting[ 'column' ], $sorting[ 'direction' ] )
                ->join( $orderTable, $orderTable . '.id', '=', $orderProductTable . '.order_id' )
                ->where( $orderTable . '.created_at', '>=', $report[ 'startDate' ] )
                ->where( $orderTable . '.created_at', '<=', $report[ 'endDate' ] )
                ->whereIn( $orderTable . '.payment_status', [ Order::PAYMENT_PAID ])
                ->get()
                ->map( function( $product ) {
                    $product->difference    =   0;
                    return $product;
                });
        }

        foreach( $previousDates[ 'current' ][ 'products' ] as $id => &$product ) {
            $default                =   new stdClass;
            $default->total_price   =   0;
            $default->quantity      =   0;
        
            $oldProduct                 =   collect( $previousDates[ 'previous' ][ 'products' ] )->filter( function( $previousProduct ) use ( $product ) {
                return $previousProduct->product_id === $product->product_id;
            })->first() ?: $default;

            $product->old_total_price   =   $oldProduct->total_price;
            $product->old_quantity      =   $oldProduct->quantity ?? 0;
            $product->difference        =   $oldProduct->total_price > 0 ? $this->getDiff(
                $oldProduct->total_price,
                $product->total_price
            ) : 100;

            $product->evolution      =   $product->quantity > $oldProduct->quantity ? 'progress' : 'regress';
        }

        $previousDates[ 'current' ][ 'total_price' ]    =   collect( $previousDates[ 'current' ][ 'products' ] )
            ->map( fn( $product ) => $product->total_price )
            ->sum();

        $previousDates[ 'previous' ][ 'total_price' ]    =   collect( $previousDates[ 'previous' ][ 'products' ] )
            ->map( fn( $product ) => $product->total_price )
            ->sum();

        return $previousDates;
    }

    /**
     * Will return a report based
     * on the requested type
     * @param string $start
     * @param string $end
     * @param string $type
     * @return array
     */
    public function getSaleReport( $start, $end, $type, $user_id = null )
    {
        switch( $type ) {
            case 'products_report':
                return $this->getProductsReports( $start, $end, $user_id );
            break;
            case 'categories_report':
            case 'categories_summary':
                return $this->getCategoryReports( $start, $end, $orderAttribute = 'name', $orderDirection = 'desc', $user_id );
            break;
        }
    }

    private function getSalesSummary( $orders )
    {
        $allSales   =   $orders->map( function( $order ) {
            $salesTaxes             =   $order->tax_value - $order->products->sum( 'tax_value' );

            return [
                'sales_discounts'   =>  $order->discount,
                'sales_taxes'       =>  $salesTaxes > 0 ? $salesTaxes : 0,
                'producs_taxes'     =>  $order->products->sum( 'tax_value' ),
                'total'             =>  $order->total - $order->products->sum( 'tax_value' ),
                'subtotal'          =>  $order->subtotal,
            ];
        });

        return [
            'sales_discounts'   =>  Currency::define( $allSales->sum( 'sales_discounts' ) )->getRaw(),
            'producs_taxes'     =>  Currency::define( $allSales->sum( 'producs_taxes' ) )->getRaw(),
            'sales_taxes'       =>  Currency::define( $allSales->sum( 'sales_taxes' ) )->getRaw(),
            'subtotal'          =>  Currency::define( $allSales->sum( 'subtotal' ) )->getRaw(),
            'total'             =>  Currency::define( $allSales->sum( 'total' ) )->getRaw(),
        ];
    }

    public function getProductsReports( $start, $end, $user_id = null )
    {
        $request     =   Order::paymentStatus( Order::PAYMENT_PAID )
            ->from( $start )
            ->to( $end );

        if ( ! empty( $user_id ) ) {
            $request    =   $request->where( 'author', $user_id );
        }
            
        $orders         =   $request->with( 'products' )
            ->get();

        $summary        =   $this->getSalesSummary( $orders );

        $products       =   $orders->map( fn( $order ) => $order->products )->flatten();

        $productsIds    =   $products->map( fn( $product ) => $product->product_id )->unique();
        
        return [
            'result'    =>  $productsIds->map( function( $id ) use ( $products ) {
                $product            =   $products->where( 'product_id', $id )->first();
                $filtredProdcuts    =   $products->where( 'product_id', $id )->all();
                
                $summable           =   [ 'quantity', 'discount', 'wholesale_tax_value', 'sale_tax_value', 'tax_value', 'total_gross_price', 'total_price', 'total_net_price', 'total_purchase_price' ];
                foreach( $summable as $key ) {
                    $product->$key  =   collect( $filtredProdcuts )->sum( $key );
                }
    
                return $product;
            })->values(),
            'summary'   =>  $summary
        ];
    }

    public function getCategoryReports( $start, $end, $orderAttribute = 'name', $orderDirection = 'desc', $user_id = null )
    {
        $request     =   Order::paymentStatus( Order::PAYMENT_PAID )
            ->from( $start )
            ->to( $end );

        if ( ! empty( $user_id ) ) {
            $request    =   $request->where( 'author', $user_id );
        }

        $orders     =   $request->with( 'products' )->get();

        /**
         * We'll pull the sales
         * summary
         */
        $summary        =   $this->getSalesSummary( $orders );

        $products       =   $orders->map( fn( $order ) => $order->products )->flatten();
        $category_ids   =   $orders->map( fn( $order ) => $order->products->map( fn( $product ) => $product->product_category_id ) );

        $unitIds        =   $category_ids->flatten()->unique()->toArray();

        /**
         * We'll get all category that are listed
         * on the product sold
         */
        $categories     =   ProductCategory::whereIn( 'id', $unitIds )
            ->orderBy( $orderAttribute, $orderDirection )
            ->get();

        /**
         * That will sum all the total prices
         */
        $categories->each( function( $category ) use( $products ) {
            $category->products             =   collect( $products->where( 'product_category_id', $category->id )->all() )->values();
            $category->total_tax_value      =   collect( $category->products )->sum( 'tax_value' );
            $category->total_price          =   collect( $category->products )->sum( 'total_price' );
            $category->total_discount       =   collect( $category->products )->sum( 'discount' );
            $category->total_sold_items     =   collect( $category->products )->sum( 'quantity' );
        });

        return [
            'result'    =>  $categories->toArray(),
            'summary'   =>  $summary
        ];
    }

    /**
     * Will returns the details for a specific cashier
     */
    public function getCashierDashboard( $cashier, $startDate = null, $endDate = null )
    {
        $cacheKey   =   'cashier-report-' . $cashier;

        if ( ! empty( request()->query( 'refresh' ) ) ) {
            Cache::forget( $cacheKey );
        }

        return Cache::remember( $cacheKey, now()->addDay(1), function() use ( $startDate, $cashier, $endDate ) {
            $startDate  =   $startDate === null ? ns()->date->getNow()->startOfDay()->toDateTimeString() : $startDate;
            $endDate    =   $endDate === null ? ns()->date->getNow()->endOfDay()->toDateTimeString() : $endDate;

            $totalSales     =   Order::paid()
                ->where( 'author', $cashier )
                ->count();

            $todaySales   =   Order::paid()
                ->where( 'created_at', '>=', $startDate )
                ->where( 'created_at', '<=', $endDate )
                ->where( 'author', $cashier )
                ->count();

            $totalSalesAmount   =   Order::paid()
                ->where( 'author', $cashier )
                ->sum( 'total' );

            $todaySalesAmount   =   Order::paid()
                ->where( 'created_at', '>=', $startDate )
                ->where( 'created_at', '<=', $endDate )
                ->where( 'author', $cashier )
                ->sum( 'total' );    

            $totalRefundsAmount   =   Order::refunded()
                ->where( 'author', $cashier )
                ->sum( 'total' );

            $todayRefunds   =   Order::refunded()
                ->where( 'created_at', '>=', $startDate )
                ->where( 'created_at', '<=', $endDate )
                ->where( 'author', $cashier )
                ->sum( 'total' );        

            $totalCustomers   =   Customer::where( 'author', $cashier )
                ->count();

            $todayCustomers   =   Customer::where( 'created_at', '>=', $startDate )
                ->where( 'created_at', '<=', $endDate )
                ->where( 'author', $cashier )
                ->count();        

            /**
             * This will compute the cashier
             * commissions and displays on his dashboard.
             */
            $module         =   app()->make( ModulesService::class );
            $config         =   [];

            if ( $module->getIfEnabled( 'NsCommissions' ) ) {
                $config     =   [
                    'today_commissions' =>  EarnedCommission::for( Auth::id() )
                        ->where( 'created_at', '>=', ns()->date->getNow()->copy()->startOfDay()->toDateTimeString() )
                        ->where( 'created_at', '<=', ns()->date->getNow()->copy()->endOfDay()->toDateTimeString() )
                        ->sum( 'value' ),
                    'total_commissions' =>  EarnedCommission::for( Auth::id() )
                        ->sum( 'value' )
                ];
            }
                
            return array_merge([
                'total_sales_count'     =>  $totalSales,
                'today_sales_count'     =>  $todaySales,
                'total_sales_amount'    =>  $totalSalesAmount,
                'today_sales_amount'    =>  $todaySalesAmount,
                'total_refunds_amount'  =>  $totalRefundsAmount,
                'today_refunds_amount'  =>  $todayRefunds,
                'total_customers'       =>  $totalCustomers,
                'today_customers'       =>  $todayCustomers,
                'today_orders'          =>  Order::where( 'created_at', '>=', $startDate )
                    ->where( 'created_at', '<=', $endDate )
                    ->where( 'author', $cashier )
                    ->orderBy( 'id', 'desc' )
                    ->get()
            ], $config );
        });
    }

    /**
     * @param int $year
     * @return array $response
     */
    public function computeYearReport( $year )
    {
        $date           =   ns()->date->copy();
        $date->year     =   $year;
        $startOfYear    =   $date->startOfYear();
        $endOfYear      =   $date->endOfYear();

        while( ! $startOfYear->isSameMonth( $endOfYear ) ) {
            $this->computeDashboardMonth( $startOfYear->copy() );
            $startOfYear->addMonth();
        }

        return [
            'status'    =>  'success',
            'message'   =>  __( 'The report has been computed successfully.' )
        ];
    }

    /**
     * Will retrun products having low stock
     * @return array $products
     */
    public function getLowStockProducts()
    {
        return ProductUnitQuantity::with( 'product', 'unit' )->whereRaw( 'low_quantity > quantity' )->get();
    }

    public function recomputeCashFlow( $fromDate, $toDate )
    {
        CashFlow::truncate();
        DashboardDay::truncate();
        DashboardMonth::truncate();
        
        $startDateString    =   $fromDate->startOfDay()->toDateTimeString();
        $endDateString      =   $toDate->endOfDay()->toDateTimeString();
                

        /**
         * @var ExpenseService
         */
        $expenseService     =   app()->make( ExpenseService::class );
        
        $expenseService->recomputeCashFlow( 
            $startDateString, 
            $endDateString
        );

        $days       =   ns()->date->getDaysInBetween( $fromDate, $toDate );

        foreach( $days as $day ) {
            $this->computeDayReport( 
                $day->startOfDay()->toDateTimeString(), 
                $day->endOfDay()->toDateTimeString()
            );

            $this->computeDashboardMonth( $day );
        }
    }
}