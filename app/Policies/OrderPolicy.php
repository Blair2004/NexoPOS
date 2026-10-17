<?php

namespace App\Policies;

use App\Models\Order;
use App\Models\User;
use App\Services\OrderAccessService;

class OrderPolicy
{
    public function __construct( private OrderAccessService $orderAccessService ) {}

    public function view( User $user, Order $order ): bool
    {
        return $this->orderAccessService->canAccess( $user, $order );
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update( User $user, Order $order ): bool
    {
        return $this->view( $user, $order );
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete( User $user, Order $order ): bool
    {
        return $this->view( $user, $order );
    }
}
