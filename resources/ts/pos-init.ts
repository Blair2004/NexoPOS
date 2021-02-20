import { ProductQuantityPromise } from "./pages/dashboard/pos/queues/products/product-quantity";
import { ProductUnitPromise } from "./pages/dashboard/pos/queues/products/product-unit";
import { Subject, BehaviorSubject, forkJoin } from "rxjs";
import { Product } from "./interfaces/product";
import { Customer } from "./interfaces/customer";
import { OrderType } from "./interfaces/order-type";
import { POSVirtualStock } from "./interfaces/pos-virual-stock";
import Vue from 'vue';
import { Order } from "./interfaces/order";
import { nsEvent, nsHooks, nsHttpClient, nsSnackBar } from "./bootstrap";
import { PaymentType } from "./interfaces/payment-type";
import { Payment } from "./interfaces/payment";
import { Responsive } from "./libraries/responsive";
import { Popup } from "./libraries/popup";
import { OrderProduct } from "./interfaces/order-product";
import { StatusResponse } from "./status-response";
import { __ } from "./libraries/lang";

/**
 * these are dynamic component
 * that are loaded conditionally
 */
const NsPosDashboardButton      =   (<any>window).NsPosDashboardButton         =   require( './pages/dashboard/pos/header-buttons/ns-pos-dashboard-button' ).default;
const NsPosPendingOrderButton   =   (<any>window).NsPosPendingOrderButton      =   require( './pages/dashboard/pos/header-buttons/ns-pos-' + 'pending-orders' + '-button' ).default;
const NsPosOrderTypeButton      =   (<any>window).NsPosOrderTypeButton         =   require( './pages/dashboard/pos/header-buttons/ns-pos-' + 'order-type' + '-button' ).default;
const NsPosCustomersButton      =   (<any>window).NsPosCustomersButton         =   require( './pages/dashboard/pos/header-buttons/ns-pos-' + 'customers' + '-button' ).default;
const NsPosResetButton          =   (<any>window).NsPosResetButton              =   require( './pages/dashboard/pos/header-buttons/ns-pos-' + 'reset' + '-button' ).default;
const NsPosCashRegister         =   (<any>window).NsPosCashRegister             =   require( './pages/dashboard/pos/header-buttons/ns-pos-' + 'registers' + '-button' ).default;
const NsAlertPopup              =   (<any>window).NsAlertPopup                 =   require( './popups/ns-' + 'alert' + '-popup' ).default;
const NsConfirmPopup            =   (<any>window).NsConfirmPopup               =   require( './popups/ns-pos-' + 'confirm' + '-popup' ).default;
const NsPromptPopup             =   (<any>window).NsPromptPopup               =   require( './popups/ns-' + 'prompt' + '-popup' ).default;
const NsLayawayPopup            =   (<any>window).NsLayawayPopup               =   require( './popups/ns-pos-' + 'layaway' + '-popup' ).default;

export class POS {
    private _products: BehaviorSubject<OrderProduct[]>;
    private _breadcrumbs: BehaviorSubject<any[]>;
    private _customers: BehaviorSubject<Customer[]>;
    private _settings: BehaviorSubject<{ [ key: string] : any}>;
    private _types: BehaviorSubject<OrderType[]>;
    private _paymentsType: BehaviorSubject<PaymentType[]>;
    private _order: BehaviorSubject<Order>;
    private _screen: BehaviorSubject<string>;
    private _initialQueue: (() => Promise<StatusResponse>)[]     =   [];
    private _options: BehaviorSubject<{ [key:string] : any}>;
    private _responsive         =   new Responsive;
    private _visibleSection: BehaviorSubject<'cart' | 'grid' | 'both'>;
    private _isSubmitting           =   false;
    private _processingAddQueue     =   false;
    private defaultOrder            =   (): Order => {
        const order: Order     =   {
            discount_type: null,
            title: '',
            discount: 0,
            register_id: this.get( 'register' ) ? this.get( 'register' ).id : undefined, // everytime it reset, this value will be pulled.
            discount_percentage: 0,
            subtotal: 0,
            total: 0,
            coupons: [],
            total_coupons : 0,
            tendered: 0,
            note: '',
            note_visibility: 'hidden',
            tax_group_id: undefined,
            tax_type: undefined,
            taxes: [], 
            tax_groups: [],
            payment_status: undefined,
            customer_id: undefined,
            change: 0,
            total_products: 0,
            shipping: 0,
            tax_value: 0,
            shipping_rate: 0,
            shipping_type: undefined,
            customer: undefined,
            type: undefined,
            products: [],
            instalments: [],
            payments: [],
            addresses: {
                shipping: undefined,
                billing: undefined
            }
        }

        return order;
    }

    constructor() {
        this.initialize();
    }

    get screen() {
        return this._screen;
    }

    get visibleSection() {
        return this._visibleSection;
    }

    get paymentsType() {
        return this._paymentsType;
    }

    get order() {
        return this._order;
    }

    get types() {
        return this._types;
    }

    get products() {
        return this._products;
    }

    get customers() {
        return this._customers;
    }

    get options() {
        return this._options;
    }

    get settings() {
        return this._settings;
    }

    get breadcrumbs() {
        return this._breadcrumbs;
    }

    get initialQueue() {
        return this._initialQueue;
    }

    get processingAddQueue() {
        return this._processingAddQueue;
    }

    reset() {
        this._isSubmitting  =   false;

        /**
         * to reset order details
         */
        this.order.next( this.defaultOrder() );
        this._products.next([]);
        this._customers.next([]);
        this._breadcrumbs.next([]);
        this.defineCurrentScreen();

        this.processInitialQueue();
        this.refreshCart();
    }

    public initialize()
    {
        this._products          =   new BehaviorSubject<OrderProduct[]>([]);
        this._customers         =   new BehaviorSubject<Customer[]>([]);
        this._types             =   new BehaviorSubject<OrderType[]>([]);
        this._breadcrumbs       =   new BehaviorSubject<any[]>([]);
        this._screen            =   new BehaviorSubject<string>('');
        this._paymentsType      =   new BehaviorSubject<PaymentType[]>([]);   
        this._visibleSection    =   new BehaviorSubject( 'both' );     
        this._options           =   new BehaviorSubject({});
        this._settings          =   new BehaviorSubject<{ [ key: string ] : any }>({});
        this._order             =   new BehaviorSubject<Order>( this.defaultOrder() );

        /**
         * This initial process will try to detect
         * if there is a tax group assigned on the settings
         * and set it as default tax group.
         */
        this.initialQueue.push( () => new Promise( ( resolve, reject ) => {
            const options   =   this.options.getValue();
            const order     =   this.order.getValue();

            if ( options.ns_pos_tax_group !== false ) {
                order.tax_group_id  =   options.ns_pos_tax_group;
                order.tax_type      =   options.ns_pos_tax_type;
                this.order.next( order );
            }

            return resolve({
                status: 'success',
                message: 'tax group assignated'
            });
        } ) );

        /**
         * this initial process will select the default
         * customer and assign him to the POS
         */
        this.initialQueue.push( () => new Promise( ( resolve, reject ) => {
            const options   =   this.options.getValue();
            const order     =   this.order.getValue();

            if ( options.ns_customers_default !== false ) {
                nsHttpClient.get( `/api/nexopos/v4/customers/${options.ns_customers_default}` )
                    .subscribe( customer => {
                        this.selectCustomer( customer );
                        resolve({ 
                            status: 'success',
                            message: __( 'The customer has been loaded' )
                        });
                    }, ( error ) => {
                        reject( error );
                    });
            }

            return resolve({
                status: 'success',
                message: 'tax group assignated'
            });
        } ) );
        
        /**
         * Whenever there is a change
         * on the products, we'll update
         * the cart.
         */
        this.products.subscribe( _ => {
            this.refreshCart();
        });

        /**
         * listen to type for updating
         * the order accordingly
         */
        this.types.subscribe( types => {
            const selected  =   types.filter( type => type.selected );

            if ( selected.length > 0 ) {
                const order     =   this.order.getValue();
                order.type      =   selected[0];
                this.order.next( order );
            }
        });

        /**
         * We're handling here the responsive aspect
         * of the POS.
         */
        window.addEventListener( 'resize', () => {
            this._responsive.detect();
            this.defineCurrentScreen();
        });

        this.defineCurrentScreen();
    }

    /**
     * This is the first initial queue
     * that runs when the POS is loaded. 
     * It also run when the pos is reset.
     * @return void
     */
    public async processInitialQueue() {
        for( let index in this._initialQueue ) {
            try {
                const response  =   await this._initialQueue[ index ]();
            } catch( exception ) {
                nsSnackBar.error( exception.message ).subscribe();
            }
        }
    }

    /**
     * This methods run as part of the verification
     * of the cart refreshing. Cannot refresh the cart.
     * @param coupon coupon
     */
    removeCoupon( coupon ) {
        const order     =   this.order.getValue();
        const coupons   =   order.coupons;
        const index     =   coupons.indexOf( coupon );
        coupons.splice( index, 1 );
        order.coupons   =   coupons;
        this.order.next( order );
    }

    pushCoupon( coupon ) {
        const order     =   this.order.getValue();

        order.coupons.forEach( _coupon => {
            if ( _coupon.code === coupon.code ) {
                const message   =   __( 'This coupon is already added to the cart' );
                nsSnackBar.error( message )
                    .subscribe();
                throw message;
            }
        })

        order.coupons.push( coupon );
        this.order.next( order );
        this.refreshCart();
    }
    
    get header() {
        /**
         * As POS object is defined on the
         * header, we can use that to reference the buttons (component)
         * that needs to be rendered dynamically
         */
        const data  =   {
            buttons: {
                NsPosDashboardButton,
                NsPosPendingOrderButton,
                NsPosOrderTypeButton,
                NsPosCustomersButton,
                NsPosResetButton,
            }
        };

        /**
         * if the cash register is enabled
         * we'll add that button to the list
         * of button available.
         */
        if ( this.options.getValue().ns_pos_registers_enabled === 'yes' ) {
            data.buttons[ 'NsPosCashRegister' ]  =   NsPosCashRegister;
        }

        return data;
    }

    defineOptions( options ) {
        this._options.next( options );
    }

    defineCurrentScreen() {
        this._visibleSection.next( [ 'xs', 'sm' ].includes( <string>this._responsive.is() ) ? 'grid' : 'both' );
        this._screen.next( <string>this._responsive.is() );
    }

    changeVisibleSection( section ) {
        if ([ 'both', 'cart', 'grid' ].includes( section ) ) {
            this._visibleSection.next( section );
        }
    }

    addPayment( payment: Payment ) {
        if ( payment.value > 0 ) {
            const order  =   this._order.getValue();
            order.payments.push( payment );
            this._order.next( order );
            
            return this.computePaid();
        }

        return nsSnackBar.error( 'Invalid amount.' ).subscribe();
    }

    removePayment( payment: Payment ) {

        if ( payment.id !== undefined ) {
            return nsSnackBar.error( 'Unable to delete a payment attached to the order' ).subscribe();
        }

        const order     =   this._order.getValue();
        const index     =   order.payments.indexOf( payment );
        order.payments.splice( index, 1 );
        this._order.next( order );

        nsEvent.emit({ 
            identifier: 'ns.pos.remove-payment',
            value: payment
        });

        this.updateCustomerAccount( payment );
        this.computePaid();
    }

    updateCustomerAccount( payment: Payment ) {
        if ( payment.identifier === 'account-payment' ) {
            const customer              =   this.order.getValue().customer;
            customer.account_amount     +=  payment.value;
            this.selectCustomer( customer );
        }
    }

    getNetPrice( value, rate, type ) {
        if ( type === 'inclusive' ) {
            return ( value / ( rate + 100 ) ) * 100;
        } else if( type === 'exclusive' ) {
            return ( ( value / 100 ) * ( rate + 100 ) );
        }
    }

    getVatValue( value, rate, type ) {
        if ( type === 'inclusive' ) {
            return value - this.getNetPrice( value, rate, type );
        } else if( type === 'exclusive' ) {
            return this.getNetPrice( value, rate, type ) - value;
        }
    }

    computeTaxes() {
        return new Promise( ( resolve, reject ) => {
            const order     =   this.order.getValue();

            if ( order.tax_group_id === undefined ) {
                return reject( false );
            }

            const groups    =   order.tax_groups;

            /**
             * if the tax group is already cached
             * we'll pull that rather than doing a new request.
             */
            if ( groups && groups[ order.tax_group_id ] !== undefined ) {
                order.taxes         =   order.taxes.map( tax => {
                    tax.tax_value   =   this.getVatValue( order.subtotal, tax.rate, order.tax_type );
                    return tax;
                });


                return resolve({
                    status: 'success',
                    data: { tax : groups[ order.tax_group_id ], order }
                });
            }

            if( order.tax_group_id !== null ) {
                nsHttpClient.get( `/api/nexopos/v4/taxes/groups/${order.tax_group_id}` )
                    .subscribe( (tax:any) => {
                        order.tax_groups    =   order.tax_groups || [];
                        order.taxes         =   tax.taxes.map( tax => {
                            return {
                                tax_id      :   tax.id,
                                tax_name    :   tax.name,
                                rate        :   parseFloat( tax.rate ),
                                tax_value   :   this.getVatValue( order.subtotal, tax.rate, order.tax_type )
                            };
                        });
    
                        /**
                         * this is set to cache the 
                         * tax group to avoid subsequent request
                         * to the server.
                         */
                        order.tax_groups[ tax.id ]    =   tax; 
    
                        return resolve({ 
                            status: 'success',
                            data : { tax, order }
                        })
                    }, ( error ) => {
                        return reject( error );
                    })
            } else {
                return reject({
                    status: 'failed',
                    message: __( 'No tax group assigned to the order' )
                })
            }
        })
    }

    /**
     * This will check if the order can be saved as layway.
     * might request additionnal information through a popup.
     * @param order Order
     */
    canProceedAsLaidAway( order: Order ) {
        return new Promise( async ( resolve, reject ) => {
            const minimalPaymentPercent     =   order.customer.group.minimal_credit_payment;
            const expected                  =   ( order.total * minimalPaymentPercent ) / 100;

            /**
             * checking order details
             * installments & payment date
             */
            if ( order.final_payment_date === undefined ) {
                try {
                    await new Promise( ( resolve, reject ) => {
                        Popup.show( NsLayawayPopup, { order, reject, resolve });
                    });
                } catch( exception ) {
                    return reject( exception );
                }
            }

            if ( order.tendered < expected ) {
                const message   =    `Before saving the order as laid away, a minimum payment of ${ Vue.filter( 'currency' )( expected ) } is required`;
                Popup.show( NsAlertPopup, { title: 'Unable to proceed', message });
                return reject({ status: 'failed', message });
            }

            return resolve({ status: 'success', message: 'Can Proceed as layaway' });
        });
    }

    /**
     * Fields might be provided to overwrite the default information 
     * set on the order. 
     * @param orderFields Object
     */
    submitOrder( orderFields = {} ) {
        return new Promise( async ( resolve, reject ) => {
            const order             =   { 
                ...<Order>this.order!.getValue(),
                ...orderFields
            };

            const minimalPayment    =   order.customer.group.minimal_credit_payment;

            /**
             * this verification applies only if the 
             * order is not "hold".
             */
            if ( order.payment_status !== 'hold' ) {
                if ( order.payments.length  === 0 ) {
                    if ( this.options.getValue().ns_orders_allow_unpaid === 'no' ) {
                        const message   =   'Please provide a payment before proceeding.';
                        return reject({ status: 'failed', message  });
                    } else if ( minimalPayment >= 0 ) {
                        try {
                            const result    =   await this.canProceedAsLaidAway( order );
                            console.log( result );
                        } catch( exception ) {
                            return reject( exception );
                        }
                    }
                }
    
                if ( order.total > order.tendered ) {
                    if ( this.options.getValue().ns_orders_allow_partial === 'no' ) {
                        const message   =   'Partially paid orders are disabled.';
                        return reject({ status: 'failed', message });
                    } else if ( minimalPayment >= 0 ) {
                        try {
                            const result    =   await this.canProceedAsLaidAway( order );
                            console.log( result );
                        } catch( exception ) {
                            return reject( exception );
                        }
                    }
                }
            }

            if ( ! this._isSubmitting ) {
                
                /**
                 * @todo do we need to set a new value here
                 * probably the passed value should be send to the server.
                 */
                const method    =   order.id !== undefined ? 'put' : 'post';
                
                this._isSubmitting  =   true;

                return nsHttpClient[ method ]( `/api/nexopos/v4/orders${ order.id !== undefined ? '/' + order.id : '' }`, order )
                    .subscribe( result => {
                        resolve( result );
                        this.reset();
                        
                        /**
                         * will trigger an acction when
                         * the order has been successfully submitted
                         */
                        nsHooks.doAction( 'ns-order-submit-successful', result );

                        this._isSubmitting  =   false;
                    }, ( error: any ) => {
                        this._isSubmitting  =   false;
                        reject( error );

                        nsHooks.doAction( 'ns-order-submit-failed', error );
                    })
            }

            return reject({ status: 'failed', message: 'An order is currently being processed.' });
        });
    }

    loadOrder( order_id ) {
        nsHttpClient.get( `/api/nexopos/v4/orders/${order_id}/pos` )
            .subscribe( ( order: any ) => {

                order       =   { ...this.defaultOrder(), ...order };

                /**
                 * We'll rebuilt the product
                 */
                const products  =   order.products.map( (orderProduct: OrderProduct ) => {
                    orderProduct.$original       =   () => orderProduct.product;
                    orderProduct.$quantities     =   () => orderProduct
                        .product
                        .unit_quantities
                        .filter( unitQuantity => unitQuantity.id === orderProduct.unit_quantity_id )[0];
                    return orderProduct;
                });

                /**
                 * we'll redefine the order type
                 */
                order.type          =   this.types.getValue().filter( type => type.identifier === order.type )[0];

                /**
                 * the address is provided differently
                 * then we need to rebuild it the way it's saved and used
                 */
                order.addresses     =   {
                    shipping    :   order.shipping_address,
                    billing     :   order.billing_address
                }

                delete order.shipping_address;
                delete order.billing_address;

                
                /**
                 * let's all set, let's load the order
                 * from now. No further change is required
                 */
                
                this.buildOrder( order );
                this.buildProducts( products );
                this.selectCustomer( order.customer );
                // this.refreshProducts( this.products.getValue() );
                // this.refreshCart();
            });
    }

    buildOrder( order ) {
        this.order.next( order );
    }

    buildProducts( products ) {
        this.products.next( products );
    }

    printOrder( order_id ) {
        const options           =   this.options.getValue();

        if ( options.ns_pos_printing_enabled_for === 'disabled' ) {
            return false;
        }

        const printSection      =   document.createElement( 'iframe' );
        printSection.id         =   'printing-section';
        printSection.className  =   'hidden';
        printSection.src        =   this.settings.getValue()[ 'urls' ][ 'printing_url' ].replace( '{id}', order_id );

        document.body.appendChild( printSection );
    }

    computePaid() {
        const order     =   this._order.getValue();   
        order.tendered      =   0;

        if ( order.payments.length > 0 ) {
            order.tendered      =   order.payments.map( p => p.value ).reduce( ( b, a ) => a + b );
        }

        if ( order.tendered >= order.total ) {
            order.payment_status    =   'paid';
        } else if ( order.tendered > 0 && order.tendered < order.total ) {
            order.payment_status    =   'partially_paid';
        } 
        
        order.change    =   order.tendered - order.total;

        this._order.next( order );
    }

    setPaymentActive( payment ) {
        const payments  =   this._paymentsType.getValue();
        const index     =   payments.indexOf( payment );
        payments.forEach( p => p.selected = false );
        payments[ index ].selected  =   true;
        this._paymentsType.next( payments );
    }

    definedPaymentsType( payments ) {
        this._paymentsType.next( payments );
    }

    selectCustomer( customer ) {
        return new Promise( ( resolve, reject ) => {
            const order         =   this.order.getValue();
            order.customer      =   customer;
            order.customer_id   =   customer.id
            this.order.next( order );
    
            /**
             * asynchronously we can load
             * customer meta data
             */
            if ( customer.group === undefined || customer.group === null ) {                              
                nsHttpClient.get( `/api/nexopos/v4/customers/${customer.id}/group` )
                    .subscribe( group => {
                        order.customer.group        =   group;
                        this.order.next( order );
                        resolve( order );
                    }, ( error ) => {
                        reject( error );
                    });
            }
        });
    }

    updateCart( current, update ) {
        for( let key in update ) {
            if ( update[ key ] !== undefined ) {
                Vue.set( current, key, update[ key ]);
            }
        }

        this.order.next( current );
        
        /**
         * explicitely here we do manually refresh the cart
         * as if we listen to cart update by subscribing,
         * that will create a loop (huge performance issue).
         */
        this.refreshCart();
    }

    /**
     * everytime the cart
     * refreshed, we might need
     * to perform some verification
     */
    checkCart() {
        const order                 =   this.order.getValue();
        const unmatchedConditions   =   [];

        order.coupons.forEach( coupon => {
            /**
             * by default we'll bypass
             * the product if it's not available
             */
            let isProductValid  =   true;

            /**
             * if the coupon includes products
             * we make sure the products are included on the cart
             */
            if ( coupon.products.length > 0 ) {
                isProductValid  =   order.products.filter( product => {
                    return coupon.products.map( p => p.product_id ).includes( product.product_id );
                }).length > 0;

                if ( ! isProductValid && unmatchedConditions.indexOf( coupon ) === -1 ) {
                    unmatchedConditions.push( coupon );
                }
            }

            /**
             * by default we'll bypass
             * the product if it's not available
             */
            let isCategoryValid  =   true;

            /**
             * if the coupon includes products
             * we make sure the products are included on the cart
             */
            if ( coupon.categories.length > 0 ) {
                isCategoryValid  =   order.products.filter( product => {
                    return coupon.categories.map( p => p.category_id ).includes( product.$original().category_id );
                }).length > 0;

                if ( ! isCategoryValid && unmatchedConditions.indexOf( coupon ) === -1 ) {
                    unmatchedConditions.push( coupon );
                }
            }
        });

        unmatchedConditions.forEach( coupon => {
            nsSnackBar.error( 
                __( 'The coupons "%s" has been removed from the cart, as it\'s required conditions are no more meet.' )
                    .replace( '%s', coupon.name ), 
                __( 'Okay' ), {
                    duration: 6000
                }
            ).subscribe();

            this.removeCoupon( coupon );
        });
    }

    async refreshCart() {
        /**
         * check if according to the product
         * available on the cart the coupons must 
         * remains the same.
         */
        this.checkCart();

        const products      =   this.products.getValue();
        let order           =   this.order.getValue();
        const productTotal  =   products
            .map( product => product.total_price );
        
        if ( productTotal.length > 0 ) {
            order.subtotal  =   productTotal.reduce( ( b, a ) => b + a );
        } else {
            order.subtotal  =   0;
        }

        /**
         * we'll compute here the value
         * of the coupons
         */
        const totalValue    =   order.coupons.map( customerCoupon => {
            if ( customerCoupon.type === 'percentage_discount' ) {
                customerCoupon.value    =   ( order.subtotal * customerCoupon.discount_value ) / 100;
                return customerCoupon.value;
            } 
            
            customerCoupon.value    =   customerCoupon.discount_value;
            return customerCoupon.value;
        });

        order.total_coupons         =   0;
        if ( totalValue.length > 0 ) {
            order.total_coupons     =   totalValue.reduce( ( before, after ) => before + after );
        }

        if ( order.discount_type === 'percentage' ) {
            order.discount   =   ( order.discount_percentage * order.subtotal ) / 100;
        }

        /**
         * if the discount amount is greather
         * than the subtotal, the discount amount
         * will be set to the order.subtotal
         */
        if ( order.discount > order.subtotal && order.total_coupons === 0 ) {
            order.discount = order.subtotal;
            nsSnackBar.info( 'The discount has been set to the cart subtotal' )
                .subscribe();
        }

        /**
         * save actual change to ensure
         * all listener are up to date.
         */
        this.order.next( order );

        /**
         * will compute the taxes based on 
         * the actual state of the order
         */
        try {
            const response  =   await this.computeTaxes();
            order           =   response[ 'data' ].order;
        } catch( exception ) {
            if ( exception !== false && exception.message !== undefined ) {
                throw exception.message;
            }
        }

        /**
         * retreive all products taxes
         * and sum the total.
         */
        const totalTaxes        =   products.map( ( product: OrderProduct ) => product.tax_value );

        /**
         * tax might be computed above the tax that currently
         * applie to the items.
         */
        order.tax_value         =   0;
        const vatType           =   this.options.getValue().ns_pos_vat;

        if( [ 'products_vat', 'products_flat_vat', 'products_variable_vat' ].includes( vatType ) && totalTaxes.length > 0 ) {
            order.tax_value    +=   totalTaxes.reduce( ( b, a ) => b + a );
        } 
        
        if ( [ 'flat_vat', 'variable_vat', 'products_variable_vat' ].includes( vatType ) && order.taxes && order.taxes.length > 0 ) {
            order.tax_value     +=   order.taxes
                .map( tax => tax.tax_value )
                .reduce( ( before, after ) => before + after );
        }

        order.total             =   ( order.subtotal + order.shipping + order.tax_value ) - order.discount - order.total_coupons;
        order.products          =   products;
        order.total_products    =   products.length

        this.order.next( order );
    }

    /**
     * Get actual stock used by the product
     * using the defined unit
     * @param product_id 
     * @param unit_id 
     */
    getStockUsage( product_id: number, unit_quantity_id: number ) {
        const stocks    =   this._products.getValue().filter( (product: OrderProduct ) => {
            return product.product_id === product_id && product.unit_quantity_id === unit_quantity_id;
        }).map( product => product.quantity );

        if ( stocks.length > 0 ) {
            return stocks.reduce( ( b, a ) => b + a );
        }

        return 0;
    }

    /**
     * this is resolved when a product is being added to the
     * cart. That will help to mutate the product before 
     * it's added the cart.
     */
    addToCartQueue  =   [
        ProductUnitPromise,
        ProductQuantityPromise
    ];

    /**
     * Process the item to add it to the cart
     * @param product 
     */
    async addToCart( product ) {

        /**
         * This is where all the mutation made by the  
         * queue promises are stored.
         */
        let productData   =   new Object;

        /**
         * Let's combien the built product
         * with the data resolved by the promises
         */
        let cartProduct: OrderProduct   =  {
            product_id          : product.id,
            name                : product.name,
            discount_type       : 'percentage',
            discount            : 0,
            discount_percentage : 0,
            quantity            : 0,
            tax_group_id        : product.tax_group_id,
            tax_value           : 0, // is computed automatically using $original()
            unit_price          : 0,
            total_price         : 0,
            mode                : 'normal',
            $original           : () => product
        };

        /**
         * will determin if the 
         * script is processing the add queue
         */
        this._processingAddQueue    =   true;

        for( let index in this.addToCartQueue ) {

            /**
             * the popup promise receives the product that
             * is above to be added. Hopefully as it's passed by reference
             * updating the product should mutate that once the queue is handled.
             */
            try {
                const promiseInstance   =   new this.addToCartQueue[ index ]( cartProduct );
                const result            =   <Object>(await promiseInstance.run( productData ));

                /**
                 * We just mix both to make sure
                 * the mutated value overwrite previously defined values.
                 */
                productData             =   { ...productData, ...result };

            } catch( brokenPromise ) {
                /**
                 * if a popup resolve "false",
                 * that means for some reason the Promise has
                 * been broken, therefore we need to stop the queue.
                 */
                if ( brokenPromise === false ) {
                    this._processingAddQueue    =   false;
                    return false;
                }
            }
        }
        
        /**
         * end proceesing add queue
         */
        this._processingAddQueue    =   false;

        /**
         * Let's combien the built product
         * with the data resolved by the promises
         */
        cartProduct   =   { ...cartProduct, ...productData };
        
        /**
         * retreive product that 
         * are currently stored
         */
        const products      =   this._products.getValue();
        
        /**
         * push the new product
         * at the front of the cart
         */
        products.unshift( cartProduct );

        /**
         * Once the product has been added to the cart
         * it's being computed
         */
        this.refreshProducts( products );

        /**
         * dispatch event that the 
         * product has been added.
         */
        this._products.next( products );
    }

    defineTypes( types ) {
        this._types.next( types );
    }

    removeProduct( product ) {
        const products  =   this._products.getValue();
        const index     =   products.indexOf( product );
        products.splice( index, 1 );
        this._products.next( products );
    }

    updateProduct( product, data ) {
        const products                      =   this._products.getValue();
        const index                         =   products.indexOf( product );

        /**
         * to ensure Vue updates accordingly.
         */
        Vue.set( products, index, { ...product, ...data });

        this.refreshProducts( products );
        this._products.next( products );
    }

    refreshProducts( products ) {
        products.forEach( product => {
            this.computeProduct( product );
        });
    }

    computeProduct( product: OrderProduct ) {
        /**
         * determining what is the 
         * real sale price
         */
        if ( product.mode === 'normal' ) {
            product.unit_price          =       product.$quantities().sale_price;
            product.tax_value           =       product.$quantities().sale_price_tax * product.quantity;
        } else {
            product.unit_price          =       product.$quantities().wholesale_price;
            product.tax_value           =       product.$quantities().wholesale_price_tax * product.quantity;
        }

        /**
         * computing the discount when it's 
         * based on a percentage
         */
        if ([ 'flat', 'percentage' ].includes( product.discount_type ) ) {
            if ( product.discount_type === 'percentage' ) {
                product.discount  =   ( ( product.unit_price * product.discount_percentage ) / 100 ) * product.quantity;
            }
        }

        product.total_price         =   ( product.unit_price * product.quantity ) - product.discount;
    }

    loadCustomer( id ) {
        return nsHttpClient.get( `/api/nexopos/v4/customers/${id}` );
    }

    defineSettings( settings ) {
        this._settings.next( settings );
    }

    voidOrder( order ) {
        if ( order.id !== undefined ) {
            if ( [ 'hold' ].includes( order.payment_status ) ) {
                Popup.show( NsConfirmPopup, {
                    title: 'Order Deletion',
                    message: 'The current order will be deleted as no payment has been made so far.',
                    onAction: ( action ) => {
                        if ( action ) {
                            nsHttpClient.delete( `/api/nexopos/v4/orders/${order.id}` )
                                .subscribe( ( result: any ) => {
                                    nsSnackBar.success( result.message ).subscribe();
                                    this.reset();
                                }, ( error ) => {
                                    return nsSnackBar.error( error.message ).subscribe();
                                })
                        }
                    }
                });
            } else {
                Popup.show( NsPromptPopup, {
                    title: 'Void The Order',
                    message: 'The current order will be void. This will cancel the transaction, but the order won\'t be deleted. Further details about the operation will be tracked on the report. Consider providing the reason of this operation.',
                    onAction: ( reason ) => {
                        if ( reason !== false ) {
                            nsHttpClient.post( `/api/nexopos/v4/orders/${order.id}/void`, { reason })
                                .subscribe( ( result: any ) => {
                                    nsSnackBar.success( result.message ).subscribe();
                                    this.reset();
                                }, ( error ) => {
                                    return nsSnackBar.error( error.message ).subscribe();
                                })
                        }
                    }
                });
            }            
        } else {
            nsSnackBar.error( 'Unable to void an unpaid order.' ).subscribe();
        }
    }

    set( key, value ) {
        const settings  =   this.settings.getValue();
        settings[ key ]     =   value;
        this.settings.next( settings );
    }

    get( key ) {
        const settings  =   this.settings.getValue();
        return settings[ key ];
    }

    destroy() {
        this._products.unsubscribe();
        this._customers.unsubscribe();
        this._types.unsubscribe();
        this._breadcrumbs.unsubscribe();
        this._paymentsType.unsubscribe();
        this._screen.unsubscribe();
        this._order.unsubscribe();
        this._settings.unsubscribe();
    }
}

( window as any ).POS       =   new POS;

export const POSInit    =   <POS>( window as any ).POS;
