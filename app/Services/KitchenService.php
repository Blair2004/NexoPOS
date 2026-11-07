<?php

namespace App\Services;

use App\Exceptions\NotAllowedException;
use App\Models\Order;
use App\Models\OrderProduct;
use App\Models\ProductCategory;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Kitchen Display System (KDS) — MVP.
 *
 * Design notes:
 * - A "kitchen zone" is a product category (nexopos_orders_products
 *   .product_category_id). No new zone table is introduced in the MVP:
 *   dishes are grouped by the category of their product, so "Bếp" and
 *   "Quầy bar" are simply categories (e.g. "Món chính", "Đồ uống").
 *   A dedicated zone mapping can replace getZones() later without
 *   changing the API shape.
 * - Per-dish progress lives on OrderProduct::$kitchen_status
 *   (pending / preparing / done). The existing `status` column
 *   (sold / refunded) is never touched: it drives refunds.
 * - Only today's orders with process_status pending/ongoing are shown.
 *   Voided and refunded orders are excluded.
 */
class KitchenService
{
    public function __construct(
        private OrdersService $ordersService,
        private Options $optionsService
    ) {
        //
    }

    public function isEnabled(): bool
    {
        return $this->optionsService->get( 'ns_kds_enabled', 'yes' ) === 'yes';
    }

    public function getLateAfterMinutes(): int
    {
        return (int) ( $this->optionsService->get( 'ns_kds_late_after_minutes', 15 ) ?: 15 );
    }

    public function getRefreshIntervalSeconds(): int
    {
        return (int) ( $this->optionsService->get( 'ns_kds_refresh_interval', 15 ) ?: 15 );
    }

    public function isAutoPrintEnabled(): bool
    {
        return $this->optionsService->get( 'ns_kds_auto_print', 'no' ) === 'yes';
    }

    /**
     * Zones (product categories) that currently have open kitchen items.
     */
    public function getZones(): Collection
    {
        $items = $this->openItemsQuery()->get( [ 'product_category_id', 'kitchen_status' ] );

        if ( $items->isEmpty() ) {
            return collect();
        }

        $names = ProductCategory::whereIn( 'id', $items->pluck( 'product_category_id' )->unique() )
            ->pluck( 'name', 'id' );

        return $items->groupBy( 'product_category_id' )
            ->map( function ( Collection $group, $categoryId ) use ( $names ) {
                return [
                    'id' => (int) $categoryId,
                    'name' => $names[ $categoryId ] ?? __( 'Uncategorized' ),
                    'pending_items' => $group->where( 'kitchen_status', OrderProduct::KITCHEN_PENDING )->count(),
                ];
            } )
            ->sortBy( 'name' )
            ->values();
    }

    /**
     * Open kitchen orders for the KDS screen, optionally filtered to one zone.
     */
    public function getKitchenOrders( ?int $zoneId = null ): array
    {
        $lateAfterMinutes = $this->getLateAfterMinutes();
        $now = Carbon::now();
        $typeLabels = $this->ordersService->getTypeLabels();

        $orders = $this->openOrdersQuery()
            ->with( [ 'customer', 'products' => function ( $query ) use ( $zoneId ) {
                $query->validProducts()
                    ->where( 'status', 'sold' )
                    ->orderBy( 'id' );

                if ( $zoneId ) {
                    $query->where( 'product_category_id', $zoneId );
                }
            } ] )
            ->orderBy( 'created_at', 'asc' )
            ->get()
            ->filter( function ( Order $order ) {
                return $order->products->isNotEmpty();
            } )
            ->values();

        $categoryNames = ProductCategory::whereIn(
            'id',
            $orders->flatMap( fn ( Order $order ) => $order->products->pluck( 'product_category_id' ) )->unique()
        )->pluck( 'name', 'id' );

        return [
            'zones' => $this->getZones()->values()->toArray(),
            'orders' => $orders->map( function ( Order $order ) use ( $now, $lateAfterMinutes, $typeLabels, $categoryNames ) {
                /**
                 * Với Carbon 3, diffInMinutes() có dấu: $now là phía
                 * gọi (caller) và giờ đơn sớm hơn nên $now->diffInMinutes()
                 * trả số âm, làm thời gian chờ hiển thị sai và highlight
                 * "chờ lâu" không bao giờ kích hoạt. Đổi chiều: tính từ
                 * giờ tạo đơn tới hiện tại thì luôn >= 0 (kẹp 0 phòng
                 * trường hợp đồng hồ lệch).
                 */
                $waitingMinutes = max( 0, (int) Carbon::parse( $order->created_at )->diffInMinutes( $now ) );

                return [
                    'id' => $order->id,
                    'code' => $order->code,
                    'type' => $order->type,
                    'type_label' => $typeLabels[ $order->type ] ?? $order->type,
                    'process_status' => $order->process_status,
                    'payment_status' => $order->payment_status,
                    'customer_name' => $order->customer?->name,
                    'note' => $order->note,
                    'created_at' => $order->created_at,
                    'waiting_minutes' => $waitingMinutes,
                    'is_late' => $waitingMinutes >= $lateAfterMinutes,
                    'items' => $order->products->map( function ( OrderProduct $product ) use ( $categoryNames ) {
                        return [
                            'id' => $product->id,
                            'name' => $product->name,
                            'quantity' => (float) $product->quantity,
                            'unit_name' => $product->unit_name,
                            'kitchen_status' => $product->kitchen_status ?: OrderProduct::KITCHEN_PENDING,
                            'category_id' => $product->product_category_id,
                            'category_name' => $categoryNames[ $product->product_category_id ] ?? __( 'Uncategorized' ),
                        ];
                    } )->values()->toArray(),
                ];
            } )->values()->toArray(),
            'late_after_minutes' => $lateAfterMinutes,
            'generated_at' => $now->toDateTimeString(),
        ];
    }

    /**
     * Mark one dish as pending / preparing / done. When every sellable
     * item of the order is done, the order itself becomes "ready"
     * (reusing the existing processing-status flow and its event).
     */
    public function setItemKitchenStatus( OrderProduct $item, string $status ): array
    {
        if ( ! in_array( $status, OrderProduct::KITCHEN_STATUSES ) ) {
            throw new NotAllowedException( __( 'The provided status is not supported.' ) );
        }

        $item->kitchen_status = $status;
        $item->save();

        $order = $item->order;
        $allDone = $order->products()
            ->validProducts()
            ->where( 'status', 'sold' )
            ->where( 'kitchen_status', '<>', OrderProduct::KITCHEN_DONE )
            ->doesntExist();

        if ( $allDone && $order->process_status !== Order::PROCESSING_READY ) {
            $this->ordersService->changeProcessingStatus( $order, Order::PROCESSING_READY );
            $order->refresh();
        }

        return [
            'status' => 'success',
            'message' => __( 'The order has been successfully updated.' ),
            'data' => [
                'item' => [
                    'id' => $item->id,
                    'kitchen_status' => $item->kitchen_status,
                ],
                'order' => [
                    'id' => $order->id,
                    'code' => $order->code,
                    'process_status' => $order->process_status,
                    'all_items_done' => $allDone,
                ],
            ],
        ];
    }

    /**
     * Items of one order that belong to a zone, for the kitchen ticket.
     * Only dishes that are not done yet are printed; when everything is
     * done, the ticket still lists them (marked as done) so the kitchen
     * can reprint a completed order if needed.
     */
    public function getTicketItems( Order $order, ?int $zoneId = null ): Collection
    {
        $items = $order->products()
            ->validProducts()
            ->where( 'status', 'sold' )
            ->orderBy( 'id' )
            ->when( $zoneId, fn ( $query ) => $query->where( 'product_category_id', $zoneId ) )
            ->get();

        $pending = $items->where( 'kitchen_status', '<>', OrderProduct::KITCHEN_DONE );

        return $pending->isNotEmpty() ? $pending->values() : $items;
    }

    private function openOrdersQuery()
    {
        $query = Order::query()
            ->where( 'created_at', '>=', ns()->date->copy()->startOfDay()->toDateTimeString() )
            ->whereIn( 'process_status', [ Order::PROCESSING_PENDING, Order::PROCESSING_ONGOING ] )
            ->whereNotIn( 'payment_status', [ Order::PAYMENT_VOID, Order::PAYMENT_REFUNDED ] );

        if ( auth()->user() ) {
            $query->accessibleTo( auth()->user() );
        }

        return $query;
    }

    private function openItemsQuery()
    {
        return OrderProduct::query()
            ->whereIn( 'order_id', $this->openOrdersQuery()->pluck( 'id' ) )
            ->where( 'quantity', '>', 0 )
            ->where( 'status', 'sold' );
    }
}
