<?php
namespace App\Services;

use App\Models\DashboardDay;
use App\Models\Expense;
use App\Models\ExpenseHistory;
use App\Models\Order;
use App\Models\ProductHistory;
use App\Models\Role;
use Illuminate\Support\Facades\Log;

class ReportService
{
    private $lastDay;
    private $lastDayStarts;
    private $lastDayEnds;
    private $dayStarts;
    private $dayEnds;

    public function __construct(
        DateService $dateService
    ) {
        $this->dateService  =   $dateService;
    }

    /**
     * Will compute the report for the current day
     */
    public function computeDayReport()
    {
        $this->dayStarts      =   $this->dateService->copy()->startOfDay()->toDateTimeString();
        $this->dayEnds        =   $this->dateService->copy()->endOfDay()->toDateTimeString();

        $todayReport        =   DashboardDay::forToday();
        
        if ( ! $todayReport instanceof DashboardDay ) {
            $todayReport                =   new DashboardDay;
            $todayReport->day_of_year   =   $this->dateService->dayOfYear;
        }

        $previousReport     =   DashboardDay::forLastRecentDay( $todayReport );
        
        $this->computeUnpaidOrdersCount( $previousReport, $todayReport );
        $this->computeUnpaidOrders( $previousReport, $todayReport );
        $this->computePaidOrders( $previousReport, $todayReport );
        $this->computePaidOrdersCount( $previousReport, $todayReport );
        $this->computeOrdersTaxes( $previousReport, $todayReport );
        $this->computePartiallyPaidOrders( $previousReport, $todayReport );
        $this->computePartiallyPaidOrdersCount( $previousReport, $todayReport );
        $this->computeDiscounts( $previousReport, $todayReport );
        $this->computeIncome( $previousReport, $todayReport );

        $todayReport->range_starts  =   $this->dayStarts;
        $todayReport->range_ends    =   $this->dayEnds;
        $todayReport->save();

        return $todayReport;
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
            $message            =   __( 'A stock operation (%s) has recently been detected, however the NexoPOS was\'nt able to update the report accordingly. This occurs if the daily dashboard reference has\'nt been created.' );
            $notification       =   app()->make( NotificationService::class );
            $notification->create([
                'title'         =>      __( 'Untracked Stock Operation' ),
                'description'   =>      $message,
                'url'           =>      'https://my.nexopos.com/en/troubleshooting/untracked-stock-operation'
            ])->dispatchFor( Role::namespace( 'admin' ) );

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

    public function computeIncome( $previousReport, $todayReport )
    {
        $totalIncome         =   Order::from( $this->dayStarts )
            ->to( $this->dayEnds )
            ->paymentStatus( 'unpaid' )
            ->sum( 'gross_total' );

        $todayReport->day_income    =   $totalIncome;
        $todayReport->total_income    =   ( $previousReport->total_income ?? 0 ) + $totalIncome;
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

    public function increaseTodayExpenses( ExpenseHistory $expense )
    {
        $today      =   DashboardDay::forToday();

        if ( $today instanceof DashboardDay ) {
            $yesterday                  =   DashboardDay::forLastRecentDay( $today );
            $today->day_expenses        +=  $expense->getRawOriginal( 'value' );
            $today->total_expenses      =   ( $yesterday->total_expenses ?? 0 ) + $today->day_expenses;
            $today->save();

            return [
                'status'    =>  'success',
                'message'   =>  __( 'The expense has been correctly saved.' ),
            ];
        }

        /**
         * @todo make sure outgoing link takes to relevant article
         * @var NotificationService
         */
        $message            =   __( 'A stock operation (%s) has recently been detected, however the NexoPOS was\'nt able to update the report accordingly. This occurs if the daily dashboard reference has\'nt been created.' );
        $notification       =   app()->make( NotificationService::class );
        $notification->create([
            'title'         =>      __( 'Untracked Stock Operation' ),
            'description'   =>      $message,
            'url'           =>      'https://my.nexopos.com/en/troubleshooting/untracked-stock-operation'
        ])->dispatchFor( Role::namespace( 'admin' ) );

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

    public function initializeWastedGood( $dashboardDay )
    {
        $previousReport                                 =   DashboardDay::forLastRecentDay( $dashboardDay );
        $dashboardDay->total_wasted_goods_count         =   $previousReport->total_wasted_goods_count ?? 0;
        $dashboardDay->total_wasted_goods               =   $previousReport->total_wasted_goods ?? 0;
        $dashboardDay->save();
    }
}