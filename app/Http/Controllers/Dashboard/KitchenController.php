<?php

/**
 * NexoPOS Kitchen Display (KDS) Controller — MVP
 *
 * The screen itself is read-only apart from marking dishes done:
 * it polls the JSON endpoint (no realtime socket in the MVP).
 * Kitchen printing is browser printing: the ticket view reuses the
 * existing `autoprint` partial, so it opens the system print dialog
 * for the kitchen printer configured on that terminal.
 */

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\DashboardController;
use App\Models\Order;
use App\Models\OrderProduct;
use App\Models\ProductCategory;
use App\Services\KitchenService;
use App\Services\OrderAccessService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;

class KitchenController extends DashboardController
{
    public function __construct(
        private KitchenService $kitchenService,
        private OrderAccessService $orderAccessService
    ) {
        //
    }

    /**
     * Kitchen display screen.
     */
    public function showKitchen()
    {
        $this->ensureEnabled();

        return View::make( 'pages.dashboard.orders.kitchen', [
            'title' => __( 'Kitchen Display' ),
            'config' => [
                'orders_url' => ns()->url( '/api/kitchen/orders' ),
                'item_status_url' => ns()->url( '/api/kitchen/order-products/{id}/status' ),
                'ticket_url' => ns()->url( '/dashboard/orders/{order}/kitchen-ticket/{zone}?autoprint=true&dash-visibility=disabled' ),
                'refresh_interval' => $this->kitchenService->getRefreshIntervalSeconds(),
                'late_after_minutes' => $this->kitchenService->getLateAfterMinutes(),
                'auto_print' => $this->kitchenService->isAutoPrintEnabled(),
            ],
        ] );
    }

    /**
     * JSON feed polled by the kitchen screen.
     */
    public function getKitchenOrders( Request $request )
    {
        $this->ensureEnabled();

        $zoneId = $request->query( 'zone' );

        return $this->kitchenService->getKitchenOrders(
            $zoneId ? (int) $zoneId : null
        );
    }

    /**
     * Mark one dish as pending / preparing / done.
     */
    public function updateItemStatus( Request $request, OrderProduct $item )
    {
        $this->ensureEnabled();
        $this->orderAccessService->authorizeOrFail( $request->user(), $item->order );

        $request->validate( [
            'kitchen_status' => 'required|string|in:' . implode( ',', OrderProduct::KITCHEN_STATUSES ),
        ] );

        return $this->kitchenService->setItemKitchenStatus(
            $item,
            $request->input( 'kitchen_status' )
        );
    }

    /**
     * Printable kitchen ticket for one order, optionally for one zone only.
     * With ?autoprint=true the browser print dialog opens automatically.
     */
    public function kitchenTicket( Request $request, Order $order, $zone = null )
    {
        $this->ensureEnabled();
        $this->orderAccessService->authorizeOrFail( $request->user(), $order );

        $order->load( 'customer' );
        $zoneId = $zone ? (int) $zone : null;
        $zoneName = $zoneId ? ProductCategory::where( 'id', $zoneId )->value( 'name' ) : null;

        return View::make( 'pages.dashboard.orders.templates.kitchen-ticket', [
            'order' => $order,
            'items' => $this->kitchenService->getTicketItems( $order, $zoneId ),
            'zone' => $zoneName ? [ 'id' => $zoneId, 'name' => $zoneName ] : null,
            'title' => sprintf( __( 'Kitchen Ticket — %s' ), $order->code ),
        ] );
    }

    /**
     * Cài đặt ns_kds_enabled = "No" thì toàn bộ KDS tắt: màn hình,
     * API, cập nhật món và phiếu bếp đều trả 404.
     */
    private function ensureEnabled(): void
    {
        if ( ! $this->kitchenService->isEnabled() ) {
            abort( 404 );
        }
    }
}
