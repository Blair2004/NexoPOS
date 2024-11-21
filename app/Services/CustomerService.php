<?php

namespace App\Services;

use App\Classes\Currency;
use App\Events\CustomerAfterUpdatedEvent;
use App\Events\CustomerBeforeDeletedEvent;
use App\Events\CustomerRewardAfterCreatedEvent;
use App\Exceptions\NotAllowedException;
use App\Exceptions\NotFoundException;
use App\Models\Coupon;
use App\Models\Customer;
use App\Models\CustomerAccountHistory;
use App\Models\CustomerAddress;
use App\Models\CustomerCoupon;
use App\Models\CustomerGroup;
use App\Models\CustomerReward;
use App\Models\Order;
use App\Models\RewardSystem;
use Carbon\Carbon;
use Exception;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;

class CustomerService
{
    /**
     * get all the defined customers
     *
     * @param array customers
     */
    public function get( $id = null )
    {
        if ( $id === null ) {
            return Customer::with( 'billing' )
                ->with( 'shipping' )
                ->where( 'group_id', '<>', null )
                ->orderBy( 'created_at', 'desc' )->get();
        } else {
            try {
                return Customer::with( 'addresses' )->findOrFail( $id );
            } catch ( Exception $exception ) {
                throw new Exception( sprintf(
                    __( 'Unable to find the customer using the provided id %s.' ),
                    $id
                ) );
            }
        }
    }

    /**
     * Retrieve the recent active customers.
     *
     * @param  int        $limit
     * @return Collection
     */
    public function getRecentlyActive( $limit = 30 )
    {
        return Customer::with( 'billing' )
            ->with( 'shipping', 'group' )
            ->where( 'group_id', '<>', null )
            ->limit( $limit )
            ->orderBy( 'updated_at', 'desc' )->get();
    }

    /**
     * Delete the customers addresses
     * using the id provided
     */
    public function deleteCustomerAttributes( int $id ): void
    {
        CustomerAddress::where( 'customer_id', $id )->delete();
    }

    /**
     * delete a specific customer
     * using a provided id
     */
    public function delete( int|Customer $id ): array
    {
        /**
         * an authorized user
         */
        if ( $id instanceof Customer ) {
            $customer = $id;
        } else {
            $customer = Customer::find( $id );

            if ( ! $customer instanceof Customer ) {
                throw new NotFoundException( __( 'Unable to find the customer using the provided id.' ) );
            }
        }

        CustomerBeforeDeletedEvent::dispatch( $customer );

        $customer->delete();

        return [
            'status' => 'success',
            'message' => __( 'The customer has been deleted.' ),
        ];
    }

    /**
     * Search customers having the defined argument.
     */
    public function search( int|string $argument ): Collection
    {
        $customers = Customer::with( [ 'billing', 'shipping', 'group' ] )
            ->orWhere( 'first_name', 'like', '%' . $argument . '%' )
            ->orWhere( 'last_name', 'like', '%' . $argument . '%' )
            ->orWhere( 'email', 'like', '%' . $argument . '%' )
            ->orWhere( 'phone', 'like', '%' . $argument . '%' )
            ->limit( 10 )
            ->get();

        return $customers;
    }

    public function precheckCustomers( array $fields, $id = null ): void
    {
        if ( $id === null ) {
            /**
             * Let's find if a similar customer exist with
             * the provided email
             */
            $customer = Customer::byEmail( $fields[ 'email' ] )->first();
        } else {
            /**
             * Let's find if a similar customer exist using the provided email.
             */
            $customer = Customer::byEmail( $fields[ 'email' ] )
                ->where( 'nexopos_users.id', '<>', $id )
                ->first();
        }

        if ( $customer instanceof Customer && ! empty( $fields[ 'email' ] ) ) {
            throw new NotAllowedException( sprintf( __( 'The email "%s" is already used for another customer.' ), $fields[ 'email' ] ) );
        }
    }

    /**
     * Create customer fields.
     */
    public function create( array $fields ): array
    {
        $this->precheckCustomers( $fields );

        /**
         * saving a customer
         * by looping only
         * the allowed fields
         */
        $customer = new Customer;

        foreach ( $fields as $field => $value ) {
            if ( $field !== 'address' ) {
                $customer->$field = $value;
            }
        }

        $customer->author = Auth::id();
        $customer->save();

        /**
         * Let's check if the customer
         * address informations has been provided
         */
        $address = $fields[ 'address' ];

        if ( is_array( $address ) ) {
            foreach ( $address as $type => $fields ) {
                if ( in_array( $type, [ 'billing', 'shipping' ] ) ) {
                    $customerAddress = new CustomerAddress;
                    $customerAddress->type = $type;
                    $customerAddress->author = Auth::id();
                    $customerAddress->customer_id = $customer->id;

                    foreach ( $fields as $field => $value ) {
                        $customerAddress->$field = $value;
                    }

                    $customerAddress->save();
                }
            }
        }

        $customer = $customer->fresh();
        $customer->addresses;

        return [
            'status' => 'success',
            'message' => __( 'The customer has been created.' ),
            'data' => compact( 'customer' ),
        ];
    }

    /**
     * Update a specific customer
     * using a provided informations.
     */
    public function update( int $id, array $fields ): array
    {
        $customer = Customer::find( $id );

        if ( ! $customer instanceof Customer ) {
            throw new NotFoundException( __( 'Unable to find the customer using the provided ID.' ) );
        }

        $this->precheckCustomers( $fields, $id );

        foreach ( $fields as $field => $value ) {
            if ( $field !== 'address' ) {
                $customer->$field = $value;
            }
        }

        $customer->author = Auth::id();
        $customer->update();

        /**
         * Let's check if the customer
         * address informations has been provided
         */
        $address = $fields[ 'address' ];

        if ( is_array( $address ) ) {
            foreach ( $address as $type => $addressFields ) {
                if ( in_array( $type, [ 'billing', 'shipping' ] ) ) {
                    $customerAddress = CustomerAddress::from( $customer, $type )->first();

                    /**
                     * If the customer address type has
                     * already been saved before
                     */
                    if ( $customerAddress instanceof CustomerAddress ) {
                        $customerAddress->type = $type;
                        $customerAddress->author = Auth::id();
                        $customerAddress->customer_id = $customer->id;

                        foreach ( $addressFields as $field => $value ) {
                            $customerAddress->$field = $value;
                        }

                        $customerAddress->save();
                    } else {
                        $customerAddress = new CustomerAddress;
                        $customerAddress->type = $type;
                        $customerAddress->author = Auth::id();
                        $customerAddress->customer_id = $customer->id;

                        foreach ( $addressFields as $field => $value ) {
                            $customerAddress->$field = $value;
                        }

                        $customerAddress->save();
                    }
                }
            }
        }

        $customer = $customer->fresh();
        $customer->addresses;

        return [
            'status' => 'success',
            'message' => __( 'The customer has been edited.' ),
            'data' => compact( 'customer' ),
        ];
    }

    /**
     * get customers addresses
     */
    public function getCustomerAddresses( int $id ): Collection
    {
        $customer = $this->get( $id );

        return $customer->addresses;
    }

    /**
     * Delete a specific customer
     * havign the defined email
     */
    public function deleteUsingEmail( string $email ): array
    {
        $customer = Customer::byEmail( $email )->first();

        if ( ! $customer instanceof Customer ) {
            throw new NotFoundException( __( 'Unable to find the customer using the provided email.' ) );
        }

        CustomerAddress::where( 'customer_id', $customer->id )->delete();
        $customer->delete();

        return [
            'status' => 'success',
            'message' => __( 'The customer has been deleted.' ),
        ];
    }

    /**
     * save a customer transaction.
     */
    public function saveTransaction( Customer $customer, string $operation, float $amount, ?string $description = '', array $details = [] ): array
    {
        if ( in_array( $operation, [
            CustomerAccountHistory::OPERATION_DEDUCT,
            CustomerAccountHistory::OPERATION_PAYMENT,
        ] ) && $customer->account_amount - $amount < 0 ) {
            throw new NotAllowedException( sprintf(
                __( 'Not enough credits on the customer account. Requested : %s, Remaining: %s.' ),
                Currency::fresh( abs( $amount ) ),
                Currency::fresh( $customer->account_amount ),
            ) );
        }

        /**
         * We'll pull the last recent record
         * and base on that we'll populate the
         * previous_amount
         */
        $beforeRecord = CustomerAccountHistory::where( 'customer_id', $customer->id )
            ->orderBy( 'id', 'desc' )
            ->first();

        $previousNextAmount = $beforeRecord instanceof CustomerAccountHistory ? $beforeRecord->next_amount : 0;

        /**
         * We'll make sure to define that are the previous and next amount.
         */
        if ( in_array( $operation, [
            CustomerAccountHistory::OPERATION_DEDUCT,
            CustomerAccountHistory::OPERATION_PAYMENT,
        ] ) ) {
            $next_amount = $previousNextAmount - $amount;
        } elseif ( in_array( $operation, [
            CustomerAccountHistory::OPERATION_ADD,
            CustomerAccountHistory::OPERATION_REFUND,
        ] ) ) {
            $next_amount = $previousNextAmount + $amount;
        }

        $customerAccountHistory = new CustomerAccountHistory;
        $customerAccountHistory->operation = $operation;
        $customerAccountHistory->customer_id = $customer->id;
        $customerAccountHistory->previous_amount = $previousNextAmount;
        $customerAccountHistory->amount = $amount;
        $customerAccountHistory->next_amount = $next_amount;
        $customerAccountHistory->description = $description;
        $customerAccountHistory->author = $details[ 'author' ];

        /**
         * We can now optionally provide
         * additional details while storing the customer history
         */
        if ( ! empty( $details ) ) {
            foreach ( $details as $key => $value ) {
                /**
                 * Some details must be sensitive
                 * and not changed.
                 */
                if ( ! in_array( $key, [
                    'id',
                    'next_amount',
                    'previous_amount',
                    'amount',
                ] ) ) {
                    $customerAccountHistory->$key = $value;
                }
            }
        }

        $customerAccountHistory->save();

        return [
            'status' => 'success',
            'message' => __( 'The customer account has been updated.' ),
            'data' => compact( 'customerAccountHistory' ),
        ];
    }

    /**
     * Updates the customers account.
     */
    public function updateCustomerAccount( CustomerAccountHistory $history ): void
    {
        if ( in_array( $history->operation, [
            CustomerAccountHistory::OPERATION_DEDUCT,
            CustomerAccountHistory::OPERATION_PAYMENT,
        ] ) ) {
            $history->customer->account_amount -= $history->amount;
        } elseif ( in_array( $history->operation, [
            CustomerAccountHistory::OPERATION_ADD,
            CustomerAccountHistory::OPERATION_REFUND,
        ] ) ) {
            $history->customer->account_amount += $history->amount;
        }

        $history->customer->save();
    }

    public function increasePurchases( Customer $customer, $value )
    {
        $customer->purchases_amount += $value;
        $customer->save();

        CustomerAfterUpdatedEvent::dispatch( $customer );

        return $customer;
    }

    public function decreasePurchases( Customer $customer, $value )
    {
        $customer->purchases_amount -= $value;
        $customer->save();

        CustomerAfterUpdatedEvent::dispatch( $customer );

        return $customer;
    }

    public function canReduceCustomerAccount( Customer $customer, $value )
    {
        if ( $customer->account_amount - $value < 0 ) {
            throw new NotAllowedException( __( 'The customer account doesn\'t have enough funds to proceed.' ) );
        }
    }

    /**
     * compute a reward assigned to a customer group
     * and issue a coupon if necessary
     *
     * @return void
     */
    public function computeReward( Order $order )
    {
        $order->load( 'customer.group.reward' );

        /**
         * if the customer is not assigned to a valid customer group,
         * the reward will not be computed.
         */
        if ( ! $order->customer->group instanceof CustomerGroup ) {
            return;
        }

        $reward = $order->customer->group->reward;

        if ( $reward instanceof RewardSystem ) {
            $points = 0;
            $reward->rules->each( function ( $rule ) use ( $order, &$points ) {
                if ( $order->total >= $rule->from && $order->total <= $rule->to ) {
                    $points += (float) $rule->reward;
                }
            } );

            $currentReward = CustomerReward::where( 'reward_id', $reward->id )
                ->where( 'customer_id', $order->customer->id )
                ->first();

            if ( ! $currentReward instanceof CustomerReward ) {
                $currentReward = new CustomerReward;
                $currentReward->customer_id = $order->customer->id;
                $currentReward->reward_id = $reward->id;
                $currentReward->points = 0;
                $currentReward->target = $reward->target;
                $currentReward->reward_name = $reward->name;
            }

            $currentReward->points += $points;
            $currentReward->save();
            $currentReward->load( 'reward' );

            CustomerRewardAfterCreatedEvent::dispatch( $currentReward, $order->customer, $reward );
        }
    }

    public function applyReward( CustomerReward $customerReward, Customer $customer, RewardSystem $reward )
    {
        /**
         * the user has reached or exceeded the reward.
         * we'll issue a new coupon and update the customer
         * point counter
         */
        if ( $customerReward->points >= $customerReward->target ) {
            $coupon = $reward->coupon;

            if ( $coupon instanceof Coupon ) {
                $customerCoupon = new CustomerCoupon;
                $customerCoupon->coupon_id = $coupon->id;
                $customerCoupon->name = $coupon->name;
                $customerCoupon->active = true;
                $customerCoupon->code = $coupon->code . '-' . ns()->date->format( 'dmi' );
                $customerCoupon->customer_id = $customer->id;
                $customerCoupon->limit_usage = $coupon->limit_usage;
                $customerCoupon->author = $customerReward->reward->author;
                $customerCoupon->save();

                $customerReward->points = abs( $customerReward->points - $customerReward->target );
                $customerReward->save();
            } else {
                /**
                 * @var NotificationService
                 */
                $notify = app()->make( NotificationService::class );
                $notify->create( [
                    'title' => __( 'Issuing Coupon Failed' ),
                    'description' => sprintf(
                        __( 'Unable to apply a coupon attached to the reward "%s". It looks like the coupon no more exists.' ),
                        $reward->name
                    ),
                    'identifier' => 'coupon-issuing-issue-' . $reward->id,
                    'url' => ns()->route( 'ns.dashboard.rewards-edit', [ 'reward' => $reward->id ] ),
                ] )->dispatchForGroupNamespaces( [ 'admin', 'nexopos.store.administrator' ] );
            }
        }
    }

    /**
     * load specific coupon using a code and optionnaly
     * the customer id for verification purpose.
     *
     * @param  string $customer_id
     * @return array
     */
    public function loadCoupon( string $code, $customer_id = null )
    {
        $coupon = Coupon::code( $code )
            ->with( 'products.product' )
            ->with( 'categories.category' )
            ->with( 'customers' )
            ->with( [ 'customerCoupon' => function ( $query ) use ( $customer_id ) {
                $query->where( 'customer_id', $customer_id );
            }] )
            ->with( 'groups' )
            ->first();

        if ( $coupon instanceof Coupon ) {
            if ( $coupon->customers()->count() > 0 ) {
                $customers_id = $coupon->customers()
                    ->get( 'customer_id' )
                    ->map( fn( $coupon ) => $coupon->customer_id )
                    ->flatten()
                    ->toArray();

                if ( ! in_array( $customer_id, $customers_id ) ) {
                    throw new Exception( __( 'The provided coupon cannot be loaded for that customer.' ) );
                }
            }

            if ( $coupon->groups()->count() > 0 ) {
                $customer = Customer::with( 'group' )->find( $customer_id );
                $groups_id = $coupon->groups()
                    ->get( 'group_id' )
                    ->map( fn( $coupon ) => $coupon->group_id )
                    ->flatten()
                    ->toArray();

                if ( ! in_array( $customer->group->id, $groups_id ) ) {
                    throw new Exception( __( 'The provided coupon cannot be loaded for the group assigned to the selected customer.' ) );
                }
            }

            return $coupon;
        }

        throw new Exception( __( 'Unable to find a coupon with the provided code.' ) );
    }

    /**
     * @todo this method doesn't seems used.
     */
    public function setCouponHistory( $fields, Coupon $coupon )
    {
        $customerCoupon = new CustomerCoupon;
        $customerCoupon->name = $coupon->name;
        $customerCoupon->limit_usage = $coupon->limit_usage;
        $customerCoupon->code = $coupon->code;
        $customerCoupon->coupon_id = $coupon->id;
        $customerCoupon->customer_id = $fields[ 'customer_id' ];
        $customerCoupon->order_id = $fields[ 'order_id' ];
        $customerCoupon->author = Auth::id();
        $customerCoupon->save();

        $this->setActiveStatus( $customerCoupon );

        return [
            'status' => 'sucess',
            'message' => __( 'The coupon has been updated.' ),
        ];
    }

    public function setActiveStatus( CustomerCoupon $coupon )
    {
        if ( $coupon->limit_usage > $coupon->usage ) {
            $coupon->active = true;
        }

        if ( (int) $coupon->limit_usage === 0 ) {
            $coupon->active = true;
        }

        $coupon->save();
    }

    public function deleteRelatedCustomerCoupon( Coupon $coupon )
    {
        CustomerCoupon::couponID( $coupon->id )
            ->get()
            ->each( function ( $coupon ) {
                $coupon->delete();
            } );
    }

    /**
     * Will refresh the owed amount
     * for the provided customer
     */
    public function updateCustomerOwedAmount( Customer $customer )
    {
        $unpaid = Order::where( 'customer_id', $customer->id )->whereIn( 'payment_status', [
            Order::PAYMENT_UNPAID,
        ] )->sum( 'total' );

        /**
         * Change here will be negative, so we
         * want to be an absolute value.
         */
        $orders = Order::where( 'customer_id', $customer->id )->whereIn( 'payment_status', [
            Order::PAYMENT_PARTIALLY,
        ] );

        $change = abs( $orders->sum( 'change' ) );

        $customer->owed_amount = ns()->currency->define( $unpaid )->additionateBy( $change )->toFloat();
        $customer->save();
    }

    /**
     * Create customer group using
     * provided fields
     *
     * @param  array $fields
     * @param  array $group
     * @return array $response
     */
    public function createGroup( $fields, ?CustomerGroup $group = null )
    {
        if ( $group === null ) {
            $group = CustomerGroup::where( 'name', $fields[ 'name' ] )->first();
        }

        if ( ! $group instanceof CustomerGroup ) {
            $group = new CustomerGroup;
        }

        foreach ( $fields as $name => $value ) {
            $group->$name = $value;
        }

        $group->author = Auth::id();
        $group->save();

        return [
            'status' => 'sucecss',
            'message' => __( 'The group has been created.' ),
            'data' => compact( 'group' ),
        ];
    }

    /**
     * return the customer account operation label
     *
     * @param  string $label
     * @return string
     */
    public function getCustomerAccountOperationLabel( $label )
    {
        switch ( $label ) {
            case CustomerAccountHistory::OPERATION_ADD: return __( 'Crediting' );
                break;
            case CustomerAccountHistory::OPERATION_DEDUCT: return __( 'Deducting' );
                break;
            case CustomerAccountHistory::OPERATION_PAYMENT: return __( 'Order Payment' );
                break;
            case CustomerAccountHistory::OPERATION_REFUND: return __( 'Order Refund' );
                break;
            default: return __( 'Unknown Operation' );
                break;
        }
    }

    /**
     * Will increase the customer purchase
     * when an order is flagged as paid
     */
    public function increaseCustomerPurchase( Order $order )
    {
        if ( in_array( $order->payment_status, [
            Order::PAYMENT_PAID,
        ] ) ) {
            $this->increasePurchases(
                customer: $order->customer,
                value: $order->total
            );
        }
    }

    /**
     * If a customer tries to use a coupon. That coupon is assigned to his account
     * with the rule defined by the parent coupon.
     */
    public function assignCouponUsage( int $customer_id, Coupon $coupon ): CustomerCoupon
    {
        $customerCoupon = CustomerCoupon::where( 'customer_id', $customer_id )->where( 'coupon_id', $coupon->id )->first();

        if ( ! $customerCoupon instanceof CustomerCoupon ) {
            $customerCoupon = new CustomerCoupon;
            $customerCoupon->customer_id = $customer_id;
            $customerCoupon->coupon_id = $coupon->id;
            $customerCoupon->name = $coupon->name;
            $customerCoupon->author = $coupon->author;
            $customerCoupon->active = true;
            $customerCoupon->code = $coupon->code;
            $customerCoupon->limit_usage = $coupon->limit_usage;
            $customerCoupon->save();
        }

        return $customerCoupon;
    }

    public function checkCouponExistence( array $couponConfig, $fields ): Coupon
    {
        $coupon = Coupon::find( $couponConfig[ 'coupon_id' ] );

        if ( ! $coupon instanceof Coupon ) {
            throw new NotFoundException( sprintf( __( 'Unable to find a reference to the attached coupon : %s' ), $couponConfig[ 'name' ] ?? __( 'N/A' ) ) );
        }

        /**
         * we'll check if the coupon is still valid.
         */
        if ( $coupon->valid_until !== null && ns()->date->lessThan( Carbon::parse( $coupon->valid_until ) ) ) {
            throw new NotAllowedException( sprintf( __( 'Unable to use the coupon %s as it has expired.' ), $coupon->name ) );
        }

        /**
         * @todo check products on the order
         * @todo check category on the order
         */

        /**
         * We'll now check if we're about to use
         * the coupon during a period is supposed to be active.
         *
         * @todo Well we're doing this because we don't yet have a proper time picker. As we're using a date time picker
         * we're extracting the hours from it :(.
         */
        $hourStarts = ! empty( $coupon->valid_hours_start ) ? Carbon::parse( $coupon->valid_hours_start )->format( 'H:i' ) : null;
        $hoursEnds = ! empty( $coupon->valid_hours_end ) ? Carbon::parse( $coupon->valid_hours_end )->format( 'H:i' ) : null;

        if (
            $hourStarts !== null &&
            $hoursEnds !== null ) {
            $todayStartDate = ns()->date->format( 'Y-m-d' ) . ' ' . $hourStarts;
            $todayEndDate = ns()->date->format( 'Y-m-d' ) . ' ' . $hoursEnds;

            if (
                ns()->date->between(
                    date1: Carbon::parse( $todayStartDate ),
                    date2: Carbon::parse( $todayEndDate )
                )
            ) {
                throw new NotAllowedException( sprintf( __( 'Unable to use the coupon %s at this moment.' ), $coupon->name ) );
            }
        }

        /**
         * We'll now check if the customer has an ongoing
         * coupon with the provided parameters
         */
        $customerCoupon = CustomerCoupon::where( 'coupon_id', $couponConfig[ 'coupon_id' ] )
            ->where( 'customer_id', $fields[ 'customer_id' ] ?? 0 )
            ->first();

        if ( $customerCoupon instanceof CustomerCoupon ) {
            if ( ! $customerCoupon->active ) {
                throw new NotAllowedException( sprintf( __( 'You\'re not allowed to use this coupon as it\'s no longer active' ) ) );
            }

            /**
             * We're trying to use a coupon that is already exhausted
             * this should be prevented here.
             */
            if ( $customerCoupon->limit_usage > 0 && $customerCoupon->usage + 1 > $customerCoupon->limit_usage ) {
                throw new NotAllowedException( sprintf( __( 'You\'re not allowed to use this coupon it has reached the maximum usage allowed.' ) ) );
            }
        }

        return $coupon;
    }

    /**
     * Will check if a coupon is a eligible for an increase
     * and will perform a usage increase.
     */
    public function increaseCouponUsage( CustomerCoupon $customerCoupon )
    {
        if ( $customerCoupon->limit_usage > 0 ) {
            if ( $customerCoupon->usage + 1 < $customerCoupon->limit_usage ) {
                $customerCoupon->usage += 1;
                $customerCoupon->save();
            } elseif ( $customerCoupon->usage + 1 === $customerCoupon->limit_usage ) {
                $customerCoupon->usage += 1;
                $customerCoupon->active = false;
                $customerCoupon->save();
            }
        }
    }

    /**
     * returns address fields and will attempt
     * filling those field with the resource provided.
     */
    public function getAddressFields( $model = null ): array
    {
        return [
            [
                'type' => 'text',
                'name' => 'first_name',
                'value' => $model->first_name ?? '',
                'label' => __( 'First Name' ),
                'description' => __( 'Provide the billing first name.' ),
            ], [
                'type' => 'text',
                'name' => 'last_name',
                'value' => $model->last_name ?? '',
                'label' => __( 'Last Name' ),
                'description' => __( 'Provide the billing last name.' ),
            ], [
                'type' => 'text',
                'name' => 'phone',
                'value' => $model->phone ?? '',
                'label' => __( 'Phone' ),
                'description' => __( 'Billing phone number.' ),
            ], [
                'type' => 'text',
                'name' => 'address_1',
                'value' => $model->address_1 ?? '',
                'label' => __( 'Address 1' ),
                'description' => __( 'Billing First Address.' ),
            ], [
                'type' => 'text',
                'name' => 'address_2',
                'value' => $model->address_2 ?? '',
                'label' => __( 'Address 2' ),
                'description' => __( 'Billing Second Address.' ),
            ], [
                'type' => 'text',
                'name' => 'country',
                'value' => $model->country ?? '',
                'label' => __( 'Country' ),
                'description' => __( 'Billing Country.' ),
            ], [
                'type' => 'text',
                'name' => 'city',
                'value' => $model->city ?? '',
                'label' => __( 'City' ),
                'description' => __( 'City' ),
            ], [
                'type' => 'text',
                'name' => 'pobox',
                'value' => $model->pobox ?? '',
                'label' => __( 'PO.Box' ),
                'description' => __( 'Postal Address' ),
            ], [
                'type' => 'text',
                'name' => 'company',
                'value' => $model->company ?? '',
                'label' => __( 'Company' ),
                'description' => __( 'Company' ),
            ], [
                'type' => 'text',
                'name' => 'email',
                'value' => $model->email ?? '',
                'label' => __( 'Email' ),
                'description' => __( 'Email' ),
            ],
        ];
    }
}
