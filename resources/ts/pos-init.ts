import { ProductQuantityPromise } from "./pages/dashboard/pos/queues/products/product-quantity";
import { ProductUnitPromise } from "./pages/dashboard/pos/queues/products/product-unit";
import { Subject, BehaviorSubject } from "rxjs";
import { Product } from "./interfaces/product";
import { Customer } from "./interfaces/customer";
import { OrderType } from "./interfaces/order-type";
import { POSVirtualStock } from "./interfaces/pos-virual-stock";
import Vue from 'vue';
import { Order } from "./interfaces/order";
import { nsSnackBar } from "./bootstrap";
import { PaymentType } from "./interfaces/payment-type";
import { Payment } from "./interfaces/payment";
import { timeStamp } from "console";
import { Responsive } from "./libraries/responsive";

/**
 * these are dynamic component
 * that are loaded conditionally
 */
const NsPosDashboardButton      =   (<any>window).NsPosDashboardButton         =   require( './pages/dashboard/pos/header-buttons/ns-pos-dashboard-button' ).default;
const NsPosPendingOrderButton   =   (<any>window).NsPosPendingOrderButton      =   require( './pages/dashboard/pos/header-buttons/ns-pos-' + 'pending-orders' + '-button' ).default;
const NsPosOrderTypeButton      =   (<any>window).NsPosOrderTypeButton         =   require( './pages/dashboard/pos/header-buttons/ns-pos-' + 'order-type' + '-button' ).default;
const NsPosCustomersButton      =   (<any>window).NsPosCustomersButton         =   require( './pages/dashboard/pos/header-buttons/ns-pos-' + 'customers' + '-button' ).default;

export class POS {
    private _products: BehaviorSubject<Product[]>;
    private _breadcrumbs: BehaviorSubject<any[]>;
    private _customers: BehaviorSubject<Customer[]>;
    private _settings: BehaviorSubject<{ [ key: string] : any}>;
    private _types: BehaviorSubject<OrderType[]>;
    private _paymentsType: BehaviorSubject<PaymentType[]>;
    private _payments: BehaviorSubject<Payment[]>;
    private _order: BehaviorSubject<Order>;
    private _screen: BehaviorSubject<string>;
    private _responsive     =   new Responsive;
    private _visibleSection: BehaviorSubject<'cart' | 'grid' | 'both'>;

    constructor() {
        this._products          =   new BehaviorSubject<Product[]>([]);
        this._customers         =   new BehaviorSubject<Customer[]>([]);
        this._types             =   new BehaviorSubject<OrderType[]>([]);
        this._breadcrumbs       =   new BehaviorSubject<any[]>([]);
        this._payments          =   new BehaviorSubject<Payment[]>([]);
        this._screen            =   new BehaviorSubject<string>('');
        this._paymentsType      =   new BehaviorSubject<PaymentType[]>([]);   
        this._visibleSection    =   new BehaviorSubject( 'both' );       
        this._order             =   new BehaviorSubject<Order>({
            discount_type: null,
            discount_amount: 0,
            discount_percentage: 0,
            subtotal: 0,
            total: 0,
            paid: 0,
            change: 0,
            total_products: 0,
            customer: undefined,
            type: undefined,
            products: [],
        });
        this._settings          =   new BehaviorSubject<{ [ key: string ] : any }>({});
        
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

            if ( selected.length > 0  ) {
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

    get payments() {
        return this._payments;
    }

    get products() {
        return this._products;
    }

    get customers() {
        return this._customers;
    }

    get breadcrumbs() {
        return this._breadcrumbs;
    }
    
    public header   =   {
        /**
         * As POS object is defined on the
         * header, we can use that to reference the buttons (component)
         * that needs to be rendered dynamically
         */
        buttons: {
            NsPosDashboardButton,
            NsPosPendingOrderButton,
            NsPosOrderTypeButton,
            NsPosCustomersButton,
        }
    }

    defineCurrentScreen() {
        this._visibleSection.next([ 'xs', 'sm' ].includes( <string>this._responsive.is() ) ? 'grid' : 'both' );
        this._screen.next( <string>this._responsive.is() );
    }

    changeVisibleSection( section ) {
        if ([ 'both', 'cart', 'grid' ].includes( section ) ) {
            this._visibleSection.next( section );
        }
    }

    addPayment( payment ) {
        const payments  =   this._payments.getValue();
        payments.push( payment );
        this._payments.next( payments );
        this.computePaid();
    }

    removePayment( payment: Payment ) {
        const payments  =   this._payments.getValue();
        const index     =   payments.indexOf( payment );
        payments.splice( index, 1 );
        this._payments.next( payments );
        this.computePaid();
    }

    computePaid() {
        const payments  =   this._payments.getValue();
        const order     =   this._order.getValue();   
        order.paid      =   0;

        if ( payments.length > 0 ) {
            order.paid      =   payments.map( p => p.amount ).reduce( ( b, a ) => a + b );
        }

        order.change    =   order.total - order.paid;

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

    definedCustomer( customer ) {
        const order     =   this.order.getValue();
        order.customer  =   customer;
        this.order.next( order );
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

    refreshCart() {
        const products      =   this.products.getValue();
        const order         =   this.order.getValue();
        const productTotal  =   products
            .map( product => product.total_price );
        
        if ( productTotal.length > 0 ) {
            order.subtotal  =   productTotal.reduce( ( b, a ) => b + a );
        } else {
            order.subtotal  =   0;
        }

        if ( order.discount_type === 'percentage' ) {
            order.discount_amount   =   ( order.discount_percentage * order.subtotal ) / 100;
        }

        /**
         * if the discount amount is greather
         * than the subtotal, the discount amount
         * will be set to the order.subtotal
         */
        if ( order.discount_amount > order.subtotal ) {
            order.discount_amount = order.subtotal;
            nsSnackBar.info( 'The discount has been set to the cart subtotal' )
                .subscribe();
        }

        order.total         =   order.subtotal - order.discount_amount;
        order.products      =   products;

        this.order.next( order );
    }

    /**
     * Get actual stock used by the product
     * using the defined unit
     * @param product_id 
     * @param unit_id 
     */
    getStockUsage( product_id: number, unit_id: number ) {
        const stocks    =   this._products.getValue().filter( (product: Product) => {
            return product.product_id === product_id && product.unit_id === unit_id;
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
    private addToCartQueue  =   [
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
        let cartProduct: Product   =  {
            product_id: product.id,
            name: product.name,
            discount_type: 'percentage',
            discount_amount: 0,
            discount_percentage: 0,
            quantity : 0,
            sale_price : product.sale_price,
            total_price : 0,
            mode: 'normal',
            $original  : () => product
        };

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
                    return false;
                }
            }
        }

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

    computeProduct( product: Product ) {
        /**
         * determining what is the 
         * real sale price
         */
        if ( product.mode === 'normal' ) {
            product.sale_price      =       product.$original().tax_type === 'inclusive' ? product.$original().incl_tax_sale_price : product.$original().excl_tax_sale_price;
        } else {
            product.sale_price      =       product.$original().tax_type === 'inclusive' ? product.$original().incl_tax_wholesale_price : product.$original().excl_tax_wholesale_price;
        }

        /**
         * computing the discount when it's 
         * based on a percentage
         */
        if ([ 'flat', 'percentage' ].includes( product.discount_type ) ) {
            if ( product.discount_type === 'percentage' ) {
                product.discount_amount  =   ( ( product.sale_price * product.discount_percentage ) / 100 ) * product.quantity;
            }
        }

        product.total_price         =   ( product.sale_price * product.quantity ) - product.discount_amount;
    }

    defineSettings( settings ) {
        this._settings.next( settings );
    }

    destroy() {
        this._customers.unsubscribe();
        this._breadcrumbs.unsubscribe();
        this._products.unsubscribe();
        this._types.unsubscribe();
        this._order.unsubscribe();
        this._settings.unsubscribe();
        this._paymentsType.unsubscribe();
    }
}

(<any>window).POS       =   new POS;
