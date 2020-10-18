<?php
namespace App\Services;

use App\Models\DashboardDay;
use App\Models\Order;

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
        $this->lastDay        =   $this->dateService->copy()->sub( 1, 'day' );
        $this->lastDayStarts  =   $this->lastDay->startOfDay()->toDateTimeString();
        $this->lastDayEnds    =   $this->lastDay->startOfDay()->toDateTimeString();

        $this->dayStarts      =   $this->dateService->copy()->startOfDay()->toDateTimeString();
        $this->dayEnds        =   $this->dateService->copy()->endOfDay()->toDateTimeString();

        $previousReport  =   DashboardDay::from( $this->lastDayStarts )
            ->to( $this->lastDayEnds )
            ->first();

        $todayReport    =   DashboardDay::from( $this->dayStarts )
            ->to( $this->dayEnds )->first();

        if ( ! $todayReport instanceof DashboardDay ) {
            $todayReport    =   new DashboardDay;
            $todayReport->day_of_year   =   $this->dateService->dayOfYear;
        }
        
        $this->computeUnpaidOrdersCount( $previousReport, $todayReport );
        $this->computeUnpaidOrders( $previousReport, $todayReport );
        $this->computePaidOrders( $previousReport, $todayReport );
        $this->computePaidOrdersCount( $previousReport, $todayReport );
        $this->computePartiallyPaidOrders( $previousReport, $todayReport );
        $this->computePartiallyPaidOrdersCount( $previousReport, $todayReport );
        $this->computeDiscounts( $previousReport, $todayReport );
        $this->computeIncome( $previousReport, $todayReport );

        $todayReport->range_starts  =   $this->dayStarts;
        $todayReport->range_ends    =   $this->dayEnds;
        $todayReport->save();
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

    private function computeWastedGoods( $previousReport, $todayReport )
    {

    }

    private function computeTodayExpenses( $previousReport, $todayReport )
    {
        
    }
}