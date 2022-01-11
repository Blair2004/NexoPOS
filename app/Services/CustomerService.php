<?php
namespace App\Services;

use App\Classes\Currency;
use App\Crud\CustomerCouponCrud;
use App\Events\AfterCustomerAccountHistoryCreatedEvent;
use App\Events\CustomerAfterUpdatedEvent;
use App\Events\CustomerRewardAfterCouponIssuedEvent;
use App\Events\CustomerRewardAfterCreatedEvent;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use App\Models\Customer;
use App\Models\CustomerAddress;
use App\Exceptions\NotFoundException;
use App\Exceptions\NotAllowedException;
use App\Models\Coupon;
use App\Models\CustomerAccountHistory;
use App\Models\CustomerCoupon;
use App\Models\CustomerGroup;
use App\Models\CustomerReward;
use App\Models\Order;
use App\Models\RewardSystem;
use App\Models\Role;

class CustomerService
{
    /**
     * get all the defined customers
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
                $customer   =   Customer::find( $id );
                $customer->address;
                return $customer;
            } catch( Exception $exception ) {
                throw new Exception( __( 'Unable to find the customer using the provided id.' ) );
            }
        }
    }

    /**
     * delete a specific customer
     * using a provided id
     * @param int customer id
     * @return array resopnse
     */
    public function delete( $id )
    {
        /**
         * @todo dispatch event while
         * deleting a customer
         * @todo check if the action is performed by
         * an authorized user
         */
        $customer   =   Customer::find( $id );

        if ( ! $customer instanceof Customer ) {
            throw new NotFoundException( __( 'Unable to find the customer using the provided id.' ) );
        }

        Customer::find( $id )->delete();
        CustomerAddress::where( 'customer_id', $id )->delete();

        return [
            'status'    =>  'success',
            'message'   =>  __( 'The customer has been deleted.' )
        ];
    }

    /**
     * Create customer fields
     * @param array fields
     * @return array response
     */
    public function create( $fields )
    {
        /**
         * Let's find if a similar customer exist with 
         * the provided email
         */
        $customer   =   Customer::byEmail( $fields[ 'email' ] )->first();

        if ( $customer instanceof Customer ) {
            throw new NotAllowedException( sprintf( __( 'The email "%s" is already stored on another customer informations.' ), $fields[ 'email' ] ) );
        }

        /**
         * saving a customer
         * by looping only 
         * the allowed fields
         */
        $customer   =   new Customer;

        foreach( $fields as $field => $value ) {
            if ( $field !== 'address' ) {
                $customer->$field   =   $value;
            }
        }

        $customer->author       =   Auth::id();
        $customer->save();

        /**
         * Let's check if the customer
         * address informations has been provided
         */
        $address                    =   $fields[ 'address' ];

        if ( is_array( $address ) ) {

            foreach( $address as $type => $fields ) {
                if ( in_array( $type, [ 'billing', 'shipping' ] ) ) {

                    $customerAddress                =   new CustomerAddress;
                    $customerAddress->type          =   $type;
                    $customerAddress->author        =   Auth::id();
                    $customerAddress->customer_id   =   $customer->id;

                    foreach( $fields as $field => $value ) {
                        $customerAddress->$field    =   $value;
                    }
                    
                    $customerAddress->save();
                }
            }
        }

        $customer       =   $customer->fresh();
        $customer->addresses;

        return [
            'status'    =>  'success',
            'message'   =>  __( 'The customer has been created.' ),
            'data'      =>  compact( 'customer' )
        ];
    }

    /**
     * Update a specific customer
     * using a provided informations
     * @param int customer id
     * @param array data
     * @return array response
     */
    public function update( $id, array $fields )
    {
        $customer   =   Customer::find( $id );

        if ( ! $customer instanceof Customer ) {
            throw new NotFoundException( __( 'Unable to find the customer using the provided ID.' ) );
        }

        foreach( $fields as $field => $value ) {
            if ( $field !== 'address' ) {
                $customer->$field       =   $value;
            }
        }

        $customer->author           =   Auth::id();
        $customer->update();

        /**
         * Let's check if the customer
         * address informations has been provided
         */
        $address    =   $fields[ 'address' ];

        if ( is_array( $address ) ) {
            foreach( $address as $type => $addressFields ) {
                if ( in_array( $type, [ 'billing', 'shipping' ] ) ) {

                    $customerAddress            =   CustomerAddress::from( $customer, $type )->first();
                    
                    /**
                     * If the customer address type has 
                     * already been saved before
                     */
                    if ( $customerAddress instanceof CustomerAddress ) {

                        $customerAddress->type          =   $type;
                        $customerAddress->author        =   Auth::id();
                        $customerAddress->customer_id   =   $customer->id; 
    
                        foreach( $addressFields as $field => $value ) {
                            $customerAddress->$field    =   $value;
                        }
        
                        $customerAddress->save();

                    } else {

                        $customerAddress            =   new CustomerAddress;
                        $customerAddress->type      =   $type;
                        $customerAddress->author    =   Auth::id();
                        $customerAddress->customer_id   =   $customer->id;

                        foreach( $addressFields as $field => $value ) {
                            $customerAddress->$field    =   $value;
                        }
        
                        $customerAddress->save();
                    } 
                }
            }
        }
        
        $customer       =   $customer->fresh();
        $customer->addresses;

        return [
            'status'    =>  'success',
            'message'   =>  __( 'The customer has been edited.' ),
            'data'      =>  compact( 'customer' )
        ];
    }

    /**
     * get customers addresses
     * @param int customer id
     * @return array
     */
    public function getCustomerAddresses( $id )
    {
        $customer   =   $this->get( $id );
        return $customer->addresses;
    }

    /**
     * Delete a specific customer
     * who use the provided email
     * @param string email
     * @return array response
     */
    public function deleteUsingEmail( $email )
    {
        $customer       =   Customer::byEmail( $email )->first();

        if ( ! $customer instanceof Customer ) {
            throw new NotFoundException( __( 'Unable to find the customer using the provided email.' ) );
        }

        CustomerAddress::where( 'customer_id', $customer->id )->delete();
        $customer->delete();

        return [
            'status'    =>  'success',
            'message'   =>  __( 'The customer has been deleted.' )
        ];
    }

    /**
     * save customer transaction
     * @param string operation
     * @param int amount
     * @return array
     */
    public function saveTransaction( Customer $customer, $operation, $amount, $description = '' )
    {
        if ( in_array( $operation, [ 
            CustomerAccountHistory::OPERATION_DEDUCT,
            CustomerAccountHistory::OPERATION_PAYMENT,
        ]) && $customer->account_amount - $amount < 0 ) {
            throw new NotAllowedException( sprintf(
                __( 'Not enough credits on the customer account. Requested : %s, Remaining: %s.' ),
                Currency::fresh( abs( $amount ) ),
                Currency::fresh( $customer->account_amount ),
            ) );
        }

        $customerAccount                =   new CustomerAccountHistory;
        $customerAccount->operation     =   $operation;
        $customerAccount->customer_id   =   $customer->id;
        $customerAccount->amount        =   $amount;
        $customerAccount->description   =   $description;
        $customerAccount->author        =   Auth::id();
        $customerAccount->save();

        event( new AfterCustomerAccountHistoryCreatedEvent( $customerAccount ) );

        return [
            'status'    =>  'success',
            'message'   =>  __( 'The customer account has been updated.' )
        ];
    }

    public function updateCustomerAccount( CustomerAccountHistory $history )
    {
        if ( in_array( $history->operation, [ 
            CustomerAccountHistory::OPERATION_DEDUCT,
            CustomerAccountHistory::OPERATION_PAYMENT,
        ] ) ) {
            $history->customer->account_amount      -=   $history->amount;
        } else if ( in_array( $history->operation, [
            CustomerAccountHistory::OPERATION_ADD,
            CustomerAccountHistory::OPERATION_REFUND,
        ]) ) {
            $history->customer->account_amount      +=   $history->amount;
        } 

        $history->customer->save();
    }

    public function increaseOrderPurchases( Customer $customer, $value )
    {
        $customer->purchases_amount     +=  $value;
        $customer->save();

        event( new CustomerAfterUpdatedEvent( $customer ) );

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
     * @param Order $order
     * @param Customer $customer
     * @return void
     */
    public function computeReward( Order $order, Customer $customer )
    {
        $reward     =   $customer->group->reward;

        if ( $reward instanceof RewardSystem ) {
            $points      =   0;
            $reward->rules->each( function( $rule ) use ( $order, &$points ) {
                if ( $order->total >= $rule->from && $order->total <= $rule->to ) {
                    $points  +=  ( float ) $rule->reward;
                }
            });

            $currentReward      =   CustomerReward::where( 'reward_id', $reward->id )
                ->where( 'customer_id', $customer->id )
                ->first();

            if ( ! $currentReward instanceof CustomerReward ) {
                $currentReward                  =   new CustomerReward;
                $currentReward->customer_id     =   $customer->id;
                $currentReward->reward_id       =   $reward->id;
                $currentReward->points          =   0;
                $currentReward->target          =   $reward->target;
                $currentReward->reward_name     =   $reward->name;
            }

            $currentReward->points      +=  $points;
            $currentReward->save();

            event( new CustomerRewardAfterCreatedEvent( $currentReward, $customer, $reward ) );
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
            $coupon                             =   $reward->coupon;

            if ( $coupon instanceof Coupon ) {
                $customerCoupon                 =   new CustomerCoupon();
                $customerCoupon->coupon_id      =   $coupon->id;
                $customerCoupon->name           =   $coupon->name;
                $customerCoupon->active         =   true;
                $customerCoupon->code           =   $coupon->code . '-' . ns()->date->format( 'dmi' );
                $customerCoupon->customer_id    =   $customer->id;
                $customerCoupon->limit_usage    =   $coupon->limit_usage;
                $customerCoupon->author         =   0;
                $customerCoupon->save();
    
                $customerReward->points         =   abs( $customerReward->points - $customerReward->target );
                $customerReward->save();
    
                event( new CustomerRewardAfterCouponIssuedEvent( $customerCoupon ) );
            } else {
                /**
                 * @var NotificationService
                 */
                $notify     =   app()->make( NotificationService::class );
                $notify->create([
                    'title'         =>  __( 'Issuing Coupon Failed' ),
                    'description'   =>  sprintf( 
                        __( 'Unable to apply a coupon attached to the reward "%s". It looks like the coupon no more exists.' ),
                        $reward->name
                    ),
                    'identifier'    =>  'coupon-issuing-issue-' . $reward->id,
                    'url'           =>  ns()->route( 'ns.dashboard.rewards-edit', [ 'reward' => $reward->id ]),
                ])->dispatchForGroupNamespaces([ 'admin', 'nexopos.store.administrator' ]);
            }
        }
    }

    /**
     * load specific coupon using a code and optionnaly 
     * the customer id for verification purpose.
     * @param string $code
     * @param string $customer_id
     * @return array
     */
    public function loadCoupon( $code, $customer_id = null )
    {
        $coupon     =   CustomerCoupon::code( $code )
            ->with( 'coupon.products.product' )
            ->with( 'coupon.categories.category' )
            ->first();

        if ( $coupon instanceof CustomerCoupon ) {

            if ( ! $coupon->active ) {
                throw new Exception( __( 'The request coupon no longer be used as it\'s no more active.' ) );
            }

            if ( $coupon->customer_id !== 0 ) {
                if ( $customer_id === null ) {
                    throw new Exception( __( 'The coupon is issued for a customer.' ) );
                }

                if ( ( int ) $coupon->customer_id !== ( int ) $customer_id ) {
                    throw new Exception( __( 'The coupon is not issued for the selected customer.' ) );
                }
            }

            return $coupon;
        }
        
        throw new Exception( __( 'Unable to find a coupon with the provided code.' ) );
    }

    public function setCoupon( $fields, Coupon $coupon )
    {
        $customerCoupon                         =   CustomerCoupon::where([
            'coupon_id'     =>      $coupon->id,
        ])->get();        

        if ( $customerCoupon->count() === 0 ) {
            $customerCoupon                     =   new CustomerCoupon;
            $customerCoupon->name               =   $coupon->name;
            $customerCoupon->limit_usage        =   $coupon->limit_usage;
            $customerCoupon->code               =   $coupon->code;
            $customerCoupon->coupon_id          =   $coupon->id;
            $customerCoupon->customer_id        =   0; // $fields[ 'customer_id' ];
            $customerCoupon->author             =   Auth::id();
            $customerCoupon->save();

            $this->setActiveStatus( $customerCoupon );
        } else {
            $customerCoupon
                ->each( function( $customerCoupon ) use ( $coupon, $fields ) {
                    $customerCoupon->name                   =   $coupon->name;
                    $customerCoupon->limit_usage            =   $coupon->limit_usage;
                    $customerCoupon->code                   =   $coupon->code;
                    $customerCoupon->coupon_id              =   $coupon->id;
                    $customerCoupon->customer_id            =   0; // $fields[ 'general' ][ 'customer_id' ];
                    $customerCoupon->author                 =   Auth::id();
                    $customerCoupon->save();

                    $this->setActiveStatus( $customerCoupon );
                });
        }

        return [
            'status'    =>  'sucess',
            'message'   =>  __( 'The coupon has been updated.' )
        ];        
    }

    public function setActiveStatus( CustomerCoupon $coupon )
    {
        if ( $coupon->limit_usage > $coupon->usage ) {
            $coupon->active     =   true;
        }

        if ( ( int ) $coupon->limit_usage === 0 ) {
            $coupon->active     =   true;
        }

        $coupon->save();
    }

    public function deleteRelatedCustomerCoupon( Coupon $coupon )
    {
        CustomerCoupon::couponID( $coupon->id )
            ->get()
            ->each( function( $coupon ) {
                $coupon->delete();
            });
    }

    /**
     * Will refresh the owed amount
     * for the provided customer
     */
    public function updateCustomerOwedAmount( Customer $customer )
    {
        $unpaid     =   Order::where( 'customer_id', $customer->id )->whereIn( 'payment_status', [
            Order::PAYMENT_UNPAID
        ])->sum( 'total' );

        /**
         * Change here will be negative, so we
         * want to be an absolute value.
         */
        $orders     =   Order::where( 'customer_id', $customer->id )->whereIn( 'payment_status', [
            Order::PAYMENT_PARTIALLY
        ]);
        
        $change     =   abs( $orders->sum( 'change' ) );

        $customer->owed_amount  =   ns()->currency->getRaw( $unpaid + $change );
        $customer->save();
    }

    /**
     * Create customer group using
     * provided fields
     * @param array $fields
     * @param array $group
     * @return array $response
     */
    public function createGroup( $fields, CustomerGroup $group = null )
    {
        if ( $group === null ) {
            $group      =   CustomerGroup::where( 'name', $fields[ 'name' ] )->first();
        }

        if ( ! $group instanceof CustomerGroup ) {
            $group  =   new CustomerGroup;
        }

        foreach( $fields as $name => $value ) {
            $group->$name   =   $value;
        }

        $group->author      =   Auth::id();
        $group->save();

        return [
            'status'    =>  'sucecss',
            'message'   =>  __( 'The group has been created.' ),
            'data'      =>  compact( 'group' )
        ];
    }

    /**
     * return the customer account operation label
     * @param string $label
     * @return string
     */
    public function getCustomerAccountOperationLabel( $label )
    {
        switch( $label ) {
            case CustomerAccountHistory::OPERATION_ADD: return __( 'Crediting' ); break;
            case CustomerAccountHistory::OPERATION_DEDUCT: return __( 'Deducting' ); break;
            case CustomerAccountHistory::OPERATION_PAYMENT: return __( 'Order Payment' ); break;
            case CustomerAccountHistory::OPERATION_REFUND: return __( 'Order Refund' ); break;
            default: return __( 'Unknown Operation' ); break;
        }
    }
}