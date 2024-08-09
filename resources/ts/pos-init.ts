import { ProductQuantityPromise } from "./pages/dashboard/pos/queues/products/product-quantity";
import { ProductUnitPromise } from "./pages/dashboard/pos/queues/products/product-unit";
import { CustomerQueue } from "./pages/dashboard/pos/queues/order/customer-queue";
import { PaymentQueue } from "./pages/dashboard/pos/queues/order/payment-queue";
import { ProductsQueue } from "./pages/dashboard/pos/queues/order/products-queue";
import { TypeQueue } from "./pages/dashboard/pos/queues/order/type-queue";
import { BehaviorSubject } from "rxjs";
import { Customer } from "./interfaces/customer";
import { OrderType } from "./interfaces/order-type";
import { Order } from "./interfaces/order";
import { nsHooks, nsHttpClient, nsNotice, nsSnackBar } from "./bootstrap";
import { PaymentType } from "./interfaces/payment-type";
import { Payment } from "./interfaces/payment";
import { Responsive } from "./libraries/responsive";
import { Popup } from "./libraries/popup";
import { OrderProduct } from "./interfaces/order-product";
import { StatusResponse } from "./status-response";
import { __ } from "./libraries/lang";
import { ProductUnitQuantity } from "./interfaces/product-unit-quantity";
import { nsRawCurrency } from "./filters/currency";
import moment from "moment";
import { defineAsyncComponent } from "vue";
import { nsCurrency } from "./filters/currency";
import Print from "./libraries/print";
import Tax from "./libraries/tax";
import * as math from "mathjs"
import nsPosLoadingPopupVue from "./popups/ns-pos-loading-popup.vue";
import { nsAlertPopup, nsConfirmPopup, nsPromptPopup } from "./components/components";


/**
 * these are dynamic component
 * that are loaded conditionally
 */
const nsPosDashboardButton      = (<any>window).nsPosDashboardButton = defineAsyncComponent( () => import('./pages/dashboard/pos/header-buttons/ns-pos-dashboard-button.vue' ) );
const nsPosPendingOrderButton   = (<any>window).nsPosPendingOrderButton = defineAsyncComponent( () => import('./pages/dashboard/pos/header-buttons/ns-pos-' + 'pending-orders' + '-button.vue' ) );
const nsPosOrderTypeButton      = (<any>window).nsPosOrderTypeButton = defineAsyncComponent( () => import('./pages/dashboard/pos/header-buttons/ns-pos-' + 'order-type' + '-button.vue' ) );
const nsPosCustomersButton      = (<any>window).nsPosCustomersButton = defineAsyncComponent( () => import('./pages/dashboard/pos/header-buttons/ns-pos-' + 'customers' + '-button.vue' ) );
const nsPosResetButton          = (<any>window).nsPosResetButton = defineAsyncComponent( () => import('./pages/dashboard/pos/header-buttons/ns-pos-' + 'reset' + '-button.vue' ) );
const nsPosCashRegister         = (<any>window).nsPosCashRegister = defineAsyncComponent( () => import('./pages/dashboard/pos/header-buttons/ns-pos-' + 'registers' + '-button.vue' ) );
const nsLayawayPopup            = (<any>window).nsLayawayPopup = defineAsyncComponent( () => import('./popups/ns-pos-' + 'layaway' + '-popup.vue' ) );
const nsPosShippingPopup        = (<any>window).nsPosShippingPopup = defineAsyncComponent( () => import('./popups/ns-pos-' + 'shipping' + '-popup.vue' ) );

( window as any ).CustomerQueue     =   CustomerQueue;
( window as any ).PaymentQueue      =   PaymentQueue;
( window as any ).ProductsQueue     =   ProductsQueue;
( window as any ).TypeQueue         =   TypeQueue;

declare const systemOptions;
declare const systemUrls;
declare const nsEvent;

export class POS {
    private _cartButtons: BehaviorSubject<{ [key: string]: any }>;
    private _products: BehaviorSubject<OrderProduct[]>;
    private _breadcrumbs: BehaviorSubject<any[]>;
    private _customers: BehaviorSubject<Customer[]>;
    private _settings: BehaviorSubject<{ [key: string]: any }>;
    private _types: BehaviorSubject<OrderType[]>;
    private _orderTypeProcessQueue: { identifier: string, promise: (selectedType: OrderType) => Promise<StatusResponse> }[] = [];
    private _paymentsType: BehaviorSubject<PaymentType[]>;
    private _order: BehaviorSubject<Order>;
    private _screen: BehaviorSubject<string>;
    private _holdPopupEnabled = true;
    private _initialQueue: (() => Promise<StatusResponse>)[] = [];
    private _options: BehaviorSubject<{ [key: string]: any }>;
    private _responsive = new Responsive;
    private _visibleSection: BehaviorSubject<'cart' | 'grid' | 'both'>;
    private _isSubmitting = false;
    private _processingAddQueue = false;
    private _selectedPaymentType: BehaviorSubject<PaymentType>;
    private _userPermissions: BehaviorSubject<{[key:string]: any}[]>;
    
    public print: Print;

    private defaultOrder = (): Order => {
        const order: Order = {
            discount_type: null,
            title: '',
            discount: 0,
            register_id: this.get('register') ? this.get('register').id : undefined, // everytime it reset, this value will be pulled.
            discount_percentage: 0,
            subtotal: 0,
            total: 0,
            coupons: [],
            total_coupons: 0,
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
            products_exclusive_tax_value: 0,
            products_inclusive_tax_value: 0,
            total_tax_value: 0,
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
        this.print  =   new Print({
            urls: systemUrls,
            options: systemOptions
        });
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

    get selectedPaymentType() {
        return this._selectedPaymentType;
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

    get orderTypeQueue() {
        return this._orderTypeProcessQueue;
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

    get responsive() {
        return this._responsive;
    }

    get processingAddQueue() {
        return this._processingAddQueue;
    }

    get cartButtons() {
        return this._cartButtons;
    }

    async reset() {
        return new Promise(async (resolve, reject) => {
            try {
                this._isSubmitting = false;

                /**
                 * to reset order details
                 */
                this.order.next(this.defaultOrder());
                this.products.next([]);
                this._customers.next([]);
                this._breadcrumbs.next([]);
                this._cartButtons.next({});
                this.defineCurrentScreen();
                this.setHoldPopupEnabled(true);

                nsHooks.doAction( 'ns-before-cart-reset' );

                
                await this.processInitialQueue();

                nsHooks.doAction( 'ns-after-cart-changed' );
                nsHooks.doAction( 'ns-after-cart-reset' );

                resolve( true );
            } catch ( exception ) {
                reject( exception );
            }
        });
    }

    public initialize() {
        this._userPermissions = new BehaviorSubject<{ [key: string]: any }[]>([]);
        this._products = new BehaviorSubject<OrderProduct[]>([]);
        this._customers = new BehaviorSubject<Customer[]>([]);
        this._types = new BehaviorSubject<OrderType[]>([]);
        this._breadcrumbs = new BehaviorSubject<any[]>([]);
        this._screen = new BehaviorSubject<string>('');
        this._paymentsType = new BehaviorSubject<PaymentType[]>([]);
        this._visibleSection = new BehaviorSubject('both');
        this._options = new BehaviorSubject({});
        this._settings = new BehaviorSubject<{ [key: string]: any }>({});
        this._order = new BehaviorSubject<Order>(this.defaultOrder());
        this._selectedPaymentType = new BehaviorSubject<PaymentType>(null);
        this._cartButtons = new BehaviorSubject<{ [key: string]: any }>({})
        this._orderTypeProcessQueue = [
            {
                identifier: 'handle.delivery-order',
                promise: (selectedType: OrderType) => new Promise<StatusResponse>((resolve, reject) => {
                    if ( selectedType && selectedType.identifier === 'delivery') {
                        return Popup.show(nsPosShippingPopup, { resolve, reject });
                    }

                    return resolve({
                        status: 'success',
                        message: 'Proceed'
                    });
                })
            }
        ];

        this.initialQueue.push(() => new Promise((resolve, reject) => {
            nsHttpClient.get(`/api/users/permissions/` ).subscribe({
                next: (response: any) => {
                    this._userPermissions.next(response);
                    resolve( response );
                },
                error: error => {
                    reject( error );
                }
            })
        }));

        /**
         * This initial process will try to detect
         * if there is a tax group assigned on the settings
         * and set it as default tax group.
         */
        this.initialQueue.push(() => new Promise((resolve, reject) => {
            const options   = this.options.getValue();
            const order     = this.order.getValue();

            order.tax_type  = options.ns_pos_tax_type;

            if (options.ns_pos_tax_group !== false) {
                order.tax_group_id = options.ns_pos_tax_group;
                this.order.next(order);
            }

            return resolve({
                status: 'success',
                message: 'tax group assignated'
            });
        }));

        /**
         * this initial process will select the default
         * customer and assign him to the POS
         */
        this.initialQueue.push(() => new Promise((resolve, reject) => {
            const options = this.options.getValue();
            const order = this.order.getValue();

            if (options.ns_customers_default !== false) {
                nsHttpClient.get(`/api/customers/${options.ns_customers_default}`)
                    .subscribe({
                        next: customer => {
                            this.selectCustomer(customer);
                            resolve({
                                status: 'success',
                                message: __('The customer has been loaded')
                            });
                        },
                        error: (error) => {
                            nsNotice
                                .error( 
                                    __( 'An error has occured' ),
                                    __( 'Unable to select the default customer. Looks like the customer no longer exists. Consider changing the default customer on the settings.' ),
                                    {
                                        actions: {
                                            readMore: {
                                                label: __( 'Read More' ),
                                                onClick: ( instance ) => {
                                                    instance.close();
                                                    window.open( 'https://my.nexopos.com/en/documentation/troubleshooting/no-default-customer', '_blank' );
                                                }
                                            }, 
                                            close: {
                                                label: __( 'Close' ),
                                            }
                                        }
                                    })
                            reject(error);
                        }
                    });
            }

            return resolve({
                status: 'success',
                message: 'no default customer is selected.'
            });
        }));

        /**
         * Whenever there is a change
         * on the products, we'll update
         * the cart.
         */
        nsHooks.addAction( 'ns-after-cart-changed', 'listen-add-to-cart', () => this.refreshCart());

        /**
         * listen to type for updating
         * the order accordingly
         */
        this.types.subscribe(types => {
            const selected = Object.values(types).filter((type: any) => type.selected);

            if (selected.length > 0) {
                const order = this.order.getValue();
                order.type = selected[0];
                this.order.next(order);
            }
        });

        /**
         * We're handling here the responsive aspect
         * of the POS.
         */
        window.addEventListener('resize', () => {
            this._responsive.detect();
            this.defineCurrentScreen();
        });

        /**
         * This will ensure the order is not closed mistakenly.
         * @returns void
         */
        window.onbeforeunload   =   () => {
            if ( this.products.getValue().length > 0 ) {
                return __( 'Some products has been added to the cart. Would youl ike to discard this order ?' );
            }
        }
    }

    public getSalePrice(item, original) {
        if ( this.options.getValue().ns_pos_price_with_tax === 'yes' ) {
            return nsRawCurrency( item.sale_price_with_tax );
        } else {
            return nsRawCurrency( item.sale_price_without_tax );
        }
    }

    public getCustomPrice(item, original) {
        if ( this.options.getValue().ns_pos_price_with_tax === 'yes' ) {
            return nsRawCurrency( item.custom_price_with_tax );
        } else {
            return nsRawCurrency( item.custom_price_without_tax );
        }
    }

    public getWholesalePrice(item, original) {
        if ( this.options.getValue().ns_pos_price_with_tax === 'yes' ) {
            return nsRawCurrency( item.wholesale_price_with_tax );
        } else {
            return nsRawCurrency( item.wholesale_price_without_tax );
        }
    }

    public setHoldPopupEnabled(status = true) {
        this._holdPopupEnabled = status;
    }

    public getHoldPopupEnabled() {
        return this._holdPopupEnabled;
    }

    /**
     * This is the first initial queue
     * that runs when the POS is loaded. 
     * It also run when the pos is reset.
     */
    async processInitialQueue() {
        return new Promise( async ( resolve, reject ) => {
            for (let index in this._initialQueue) {
                try {
                    const response = await Promise.race([
                        this._initialQueue[index](),
                        new Promise((_, timeoutReject) => setTimeout(() => timeoutReject(new Error('Timeout')), 60000)) // 5 seconds timeout
                    ]);
                } catch (exception) {
                    reject( exception );
                    nsSnackBar.error(exception.message).subscribe();
                }
            }

            resolve( true );
        });
    }

    /**
     * This methods run as part of the verification
     * of the cart refreshing. Cannot refresh the cart.
     * @param coupon coupon
     */
    removeCoupon(coupon) {
        const order = this.order.getValue();
        const coupons = order.coupons;
        const index = coupons.indexOf(coupon);
        coupons.splice(index, 1);
        order.coupons = coupons;
        this.order.next(order);
    }

    pushCoupon(coupon) {
        const order = this.order.getValue();

        order.coupons.forEach(_coupon => {
            if (_coupon.code === coupon.code) {
                const message = __('This coupon is already added to the cart');
                nsSnackBar.error(message)
                    .subscribe();
                throw message;
            }
        })

        order.coupons.push(coupon);
        this.order.next(order);
        this.refreshCart();
    }

    get header() {
        /**
         * As POS object is defined on the
         * header, we can use that to reference the buttons (component)
         * that needs to be rendered dynamically
         */
        const data = {
            buttons: {
                nsPosDashboardButton,
                nsPosPendingOrderButton,
                nsPosOrderTypeButton,
                nsPosCustomersButton,
                nsPosResetButton,
            }
        };

        /**
         * if the cash register is enabled
         * we'll add that button to the list
         * of button available.
         */
        if (this.options.getValue().ns_pos_registers_enabled === 'yes') {
            data.buttons['nsPosCashRegister'] = nsPosCashRegister;
        }

        /**
         * expose the pos header data, for allowing
         * custom button injection.
         */
        nsHooks.doAction('ns-pos-header', data);

        return data;
    }

    defineOptions(options) {
        this._options.next(options);
    }

    defineCurrentScreen() {
        this._visibleSection.next(['xs', 'sm'].includes(<string>this._responsive.is()) ? 'grid' : 'both');
        this._screen.next(<string>this._responsive.is());
    }

    changeVisibleSection(section) {
        if (['both', 'cart', 'grid'].includes(section)) {

            if (['cart', 'both'].includes(section)) {
                this.refreshCart();
            }

            this._visibleSection.next(section);
        }
    }

    addPayment(payment: Payment) {
        if (payment.value > 0) {
            const order = this._order.getValue();
            order.payments.push(payment);
            this._order.next(order);

            return this.computePaid();
        }

        return nsSnackBar.error('Invalid amount.').subscribe();
    }

    removePayment(payment: Payment) {

        if (payment.id !== undefined) {
            return nsSnackBar.error( __( 'Unable to delete a payment attached to the order.' ) ).subscribe();
        }

        const order = this._order.getValue();
        const index = order.payments.indexOf(payment);
        order.payments.splice(index, 1);
        this._order.next(order);

        nsEvent.emit({
            identifier: 'ns.pos.remove-payment',
            value: payment
        });

        this.updateCustomerAccount(payment);
        this.computePaid();
    }

    updateCustomerAccount(payment: Payment) {
        if (payment.identifier === 'account-payment') {
            const customer = this.order.getValue().customer;
            customer.account_amount += payment.value;
            this.selectCustomer(customer);
        }
    }

    getPriceWithoutTax(value, rate, type) {
        if (type === 'inclusive') {
            return Tax.computeInclusive( value, rate );
        } else if (type === 'exclusive') {
            return value;
        }
    }

    getPriceWithTax( value, rate, type ) {
        if (type === 'inclusive') {
            return value;
        } else if (type === 'exclusive') {
            return Tax.computeExclusive( value, rate )
        }
    }

    getVatValue(value, rate, type) {
        if (type === 'inclusive') {
            return value - this.getPriceWithoutTax(value, rate, type);
        } else if (type === 'exclusive') {
            return this.getPriceWithTax(value, rate, type) - value;
        }

        return 0;
    }

    computeTaxes() {
        return new Promise((resolve, reject) => {
            let order   =   this.order.getValue();
            order       =   this.computeProductsTaxes( order );

            if (order.tax_group_id === undefined || order.tax_group_id === null) {
                this.computeOrderTaxes( order );

                return resolve({ 
                    data: { order },
                    status: 'success'
                });
            }

            const groups = order.tax_groups;

            /**
             * if the tax group is already cached
             * we'll pull that rather than doing a new request.
             */
            if (Object.values(groups).length > 0) {

                /**
                 * Only if a tax group is assigned to the 
                 * order we should then get the real VAT value.
                 */
                if ( groups[order.tax_group_id] !== undefined ) {
                    order   =   <Order>this.computeOrderTaxGroup( order, groups[order.tax_group_id] );
                }

                return resolve({
                    status: 'success',
                    data: { tax: groups[order.tax_group_id], order }
                });
            }
            
            if (order.tax_group_id !== undefined && order.tax_group_id.toString().length > 0 ) {
                nsHttpClient.get(`/api/taxes/groups/${order.tax_group_id}`)
                    .subscribe({
                        next: (tax: any) => {
                            order   =   <Order>this.computeOrderTaxGroup( order, tax );
    
                            return resolve({
                                status: 'success',
                                data: { tax, order }
                            })
                        }, 
                        error: (error) => {
                            return reject(error);
                        }
                    })
            } else {
                return reject({
                    status: 'error',
                    message: __('No tax group assigned to the order')
                })
            }
        })
    }

    computeOrderTaxGroup( order, tax ) {
        const summarizedRates   =   <number>tax.taxes.map( tax => parseFloat( tax.rate ) ).reduce( ( b, a ) => b + a );
        const currentVatValue   =   this.getVatValue( order.subtotal - order.discount, summarizedRates, order.tax_type );

        tax.taxes   =   tax.taxes.map( _tax => {
            const currentPercentage     =   math.chain( 
                math.chain( _tax.rate ).divide( summarizedRates ).done()
            ).multiply( 100 ).done();

            return {
                id: _tax.id,
                tax_id : _tax.tax_id,
                name: _tax.name,
                rate: parseFloat(_tax.rate),
                tax_value: math.chain(
                    math.chain( currentVatValue ).multiply( currentPercentage ).done()
                ).divide(100).done()
            };
        });

        if ( tax.taxes.length === 0 ) {
            nsSnackBar.error( __( 'The selected tax group doesn\'t have any assigned sub taxes. This might cause wrong figures.' ), __( 'Proceed' ), { duration: false })
                .subscribe();

            return;
        }

        order.tax_groups = order.tax_groups || [];
        order.taxes = tax.taxes;

        /**
         * this is set to cache the 
         * tax group to avoid subsequent request
         * to the server.
         */
        order.tax_groups[tax.id] = tax;

        return this.computeOrderTaxes( order );
    }

    computeOrderTaxes( order: Order ) {
        const posVat        =   this.options.getValue().ns_pos_vat;
        const priceWithTax    =   this.options.getValue().ns_pos_price_with_tax === 'yes';

        if ([ 'flat_vat', 'variable_vat', 'products_variable_vat', 'products_flat_vat' ].includes(posVat) && order.taxes && order.taxes.length > 0) {
            order.tax_value += order.taxes
                .map(tax => tax.tax_value)
                .reduce((before, after) => before + after);
        }

        /**
         * By default, we'll use box computed tax and products tax value 
         * when priceWithTax is enabled.
         * However to avoid duplicate taxes, we'll only consider computed tax
         * when priceWithTax is disabled
         */
        order.total_tax_value     =  order.tax_value;

        if ([ 'products_variable_vat', 'products_flat_vat', 'products_vat' ].includes(posVat) && ! priceWithTax ) {
            order.total_tax_value     =  order.products_exclusive_tax_value + order.tax_value;
        }

        return order;
    }

    computeProductsTaxes( order: Order ) {
        const products      =   this.products.getValue();

        /**
         * retrieve all products taxes
         * and sum the total.
         */
        const totalInclusiveTax = products.filter( product => product.tax_type === 'inclusive' ).map((product: OrderProduct) => {
            return product.tax_value;
        });

        const totalExclusiveTax = products.filter( product => product.tax_type === 'exclusive' ).map((product: OrderProduct) => {
            return product.tax_value;
        });

        /**
         * tax might be computed above the tax that currently
         * applie to the items.
         */
        order.products_exclusive_tax_value    =   0;
        order.products_inclusive_tax_value    =   0;

        const posVat    =   this.options.getValue().ns_pos_vat;

        if ([ 'products_flat_vat', 'products_variable_vat', 'products_vat' ].includes(posVat) && totalExclusiveTax.length > 0) {
            order.products_exclusive_tax_value    +=  totalExclusiveTax.reduce((b, a) => b + a);
        }

        if ([ 'products_flat_vat', 'products_variable_vat', 'products_vat' ].includes(posVat) && totalInclusiveTax.length > 0) {
            order.products_inclusive_tax_value    +=  totalInclusiveTax.reduce((b, a) => b + a);
        }

        order.products = products;
        order.total_products = products.length;

        return order;
    }

    /**
     * This will check if the order can be saved as layway.
     * might request additionnal information through a popup.
     * @param order Order
     */
    canProceedAsLaidAway(_order: Order): { status: string, message: string, data: { order: Order } } | any {
        return new Promise(async (resolve, reject) => {
            const minimalPaymentPercent = _order.customer.group.minimal_credit_payment;
            const firstPart     =   math.chain( _order.total ).multiply( minimalPaymentPercent ).done();
            let expected: any = math.chain( firstPart ).divide( 100 ).done();
            expected = parseFloat(expected);

            /**
             * checking order details
             * installments & payment date
             */
            try {
                const result = await new Promise<{order: Order}>((resolve, reject) => {
                    Popup.show(nsLayawayPopup, { order: _order, reject, resolve });
                });

                if (result.order.instalments.length === 0 && result.order.tendered < expected) {
                    const message = __(`Before saving this order, a minimum payment of {amount} is required`).replace('{amount}', nsCurrency(expected));
                    Popup.show( nsAlertPopup, { title: __('Unable to proceed'), message });
                    return reject({ status: 'error', message });
                } else {
                    const paymentType = this.selectedPaymentType.getValue();
                    const expectedSlice = result.order.instalments.filter(payment => payment.amount >= expected && moment( payment.date ).isSame( ns.date.moment.startOf( 'day' ), 'day' ) );

                    if ( expectedSlice.length === 0 ) {
                        return resolve({ status: 'success', message: __('Layaway defined'), data: { order: result.order } });
                    }

                    const firstSlice = expectedSlice[0].amount;

                    if ( firstSlice > 0 ) {
                        /**
                         * If the instalment has been configured, we'll ease things for
                         * the waiter and invite him to add the first slice as 
                         * the payment.
                         */
                        Popup.show( nsConfirmPopup, {
                            title: __(`Initial Payment`),
                            message: __(`In order to proceed, an initial payment of {amount} is required for the selected payment type "{paymentType}". Would you like to proceed ?`)
                                .replace('{amount}', nsCurrency(firstSlice))
                                .replace('{paymentType}', paymentType.label),
                            onAction: (action) => {
                                if ( action ) {
                                    const payment: Payment = {
                                        identifier: paymentType.identifier,
                                        label: paymentType.label,
                                        value: firstSlice,
                                        readonly: false,
                                        selected: true,
                                    }
        
                                    this.addPayment(payment);   
                                    
                                    /**
                                     * The expected slice
                                     * should be marked as paid once submitted
                                     */
                                    expectedSlice[0].paid   =   true;
        
                                    resolve({ status: 'success', message: __('Layaway defined'), data: { order: result.order } });
                                } else {
                                    reject({ status: 'error', message: __( 'The request was canceled' ) })
                                }
                            }
                        });
                    } else {
                        /**
                         * no payment required, let's proceed.
                         */
                        resolve({ status: 'success', message: __('Layaway defined'), data: { order: result.order } });
                    }
                }

            } catch (exception) {
                return reject(exception);
            }
        });
    }

    /**
     * Fields might be provided to overwrite the default information 
     * set on the order. 
     * @param orderFields Object
     */
    submitOrder(orderFields = {}) {
        return new Promise(async (resolve, reject) => {
            var order = {
                ...<Order>this.order!.getValue(),
                ...orderFields
            };

            const minimalPayment = order.customer.group.minimal_credit_payment;

            /**
             * this verification applies only if the 
             * order is not "hold".
             */
            if (order.payment_status !== 'hold') {
                if (order.payments.length === 0 && order.total > 0 && order.total > order.tendered) {
                    if (this.options.getValue().ns_orders_allow_partial === 'no') {
                        const message = __('Partially paid orders are disabled.');
                        return reject({ status: 'error', message });
                    } else if (minimalPayment >= 0) {
                        try {
                            const result = await this.canProceedAsLaidAway(order);

                            /**
                             * the order might have been updated
                             * by the layaway popup.
                             */
                            order = result.data.order;
                        } catch (exception) {
                            return reject(exception);
                        }
                    }
                }
            }

            if (!this._isSubmitting) {
                this._isSubmitting = true;
                return this.proceedSubmitting( order, resolve, reject );
            }

            return reject({ status: 'error', message: __('An order is currently being processed.') });
        });
    }

    /**
     * Will proceed to submit the order directly
     * @param order Order
     * @param resolve resolve callback
     * @param reject reject callback
     * @returns Subscription
     */
    proceedSubmitting( order, resolve, reject ) {
        /**
         * @todo do we need to set a new value here
         * probably the passed value should be send to the server.
         */
        const method = order.id !== undefined ? 'put' : 'post';

        /**
         * We should allow any module to mutate
         * the order before it's submitted.
         */
        nsHooks.doAction('ns-order-before-submit', order );

        return nsHttpClient[method](`/api/orders${order.id !== undefined ? '/' + order.id : ''}`, order)
            .subscribe({
                next: result => {
                    resolve(result);
                    this.reset();

                    /**
                     * will trigger an acction when
                     * the order has been successfully submitted
                     */
                    nsHooks.doAction('ns-order-submit-successful', result);

                    this._isSubmitting = false;

                    /**
                     * when all this has been executed, we can play
                     * a sound if it's enabled
                     */
                    const url     =   this.options.getValue().ns_pos_complete_sale_audio;
                    
                    if ( url.length > 0 ) {
                        ( new Audio( url ) ).play();
                    }
                },
                error: (error: any) => {
                    this._isSubmitting = false;
                    reject(error);

                    nsHooks.doAction('ns-order-submit-failed', error);
                }
            });
    }

    defineQuantities( product, units = [] ) {
        return new Promise ( ( resolve, reject ) => { 
            const unit  =   units.filter( unit => unit.id === product.unit_id );

            const quantities    =   {
                unit: unit[0] || {},
                
                sale_price_with_tax: product.mode === 'normal' ? parseFloat( product.price_with_tax ) : 0,
                sale_price_without_tax: product.mode === 'normal' ? parseFloat( product.price_without_tax ) : 0,
                sale_price: product.mode === 'normal' ? parseFloat( product.unit_price ) : 0,
                sale_price_tax: product.mode === 'normal' ? product.tax_value : 0,
                sale_price_edit: 0,

                wholesale_price_with_tax: product.mode === 'wholesale' ? parseFloat( product.price_with_tax ) : 0,
                wholesale_price_without_tax: product.mode === 'wholesale' ? parseFloat( product.price_without_tax ) : 0,
                wholesale_price: product.mode === 'wholesale' ? parseFloat( product.unit_price ) : 0,
                wholesale_price_tax: product.mode === 'wholesale' ? product.tax_value : 0,
                wholesale_price_edit: 0,

                custom_price_with_tax: product.mode === 'custom' ? parseFloat( product.price_with_tax ) : 0,
                custom_price_without_tax: product.mode === 'custom' ? parseFloat( product.price_without_tax ) : 0,
                custom_price: product.mode === 'custom' ? parseFloat( product.unit_price ) : 0,
                custom_price_tax: product.mode === 'custom' ? product.tax_value : 0,
                custom_price_edit: product.mode === 'custom' ? parseFloat( product.unit_price ) : 0,
            };

            let tax_group;

            /**
             * this will get the taxes
             * and compute it for the product
             */
            if( [ 'inclusive', 'exclusive' ].includes( product.tax_type ) ) {
                try {
                    if( product.tax_group_id ) {
                        nsHttpClient.get( `/api/taxes/groups/${product.tax_group_id}` )
                            .subscribe({
                                next: (taxGroup: any) => {
                                    [ 'sale', 'wholesale', 'custom' ].forEach( label => {
                                        quantities[ label + '_price_tax' ]  =   taxGroup.taxes.map( tax => {
                                            return this.getVatValue( quantities[ label + '_price' ], tax.rate, product.tax_type );
                                        }).reduce( ( b, a ) => b + a );

                                        quantities[ 'gross_' + label + '_price' ]  =  quantities[ label + '_price' ] + quantities[ label + '_price_tax' ];
                                        quantities[ 'net_' + label + '_price' ]  =  quantities[ label + '_price' ] - quantities[ label + '_price_tax' ];
                                    });
                                    
                                    tax_group            =   taxGroup;
        
                                    return resolve( quantities );
                                },
                                error:  error => {
                                    reject( false );
                                }
                            })
                    } else {
                        quantities.sale_price_tax       =   0;
                        quantities.wholesale_price_tax  =   0;
                        quantities.sale_price_without_tax  =  product.unit_price;
                        
                        return resolve( quantities );
                    }
                } catch( exception ) {
                    return nsSnackBar.error( __( 'An error has occurred while computing the product.' ) ).subscribe();
                }
            }
            
            return resolve( quantities );
        });
    }

    loadOrder(order_id) {
        return new Promise((resolve, reject) => {
            nsHttpClient.get(`/api/orders/${order_id}/pos`)
                .subscribe({
                    next: async (order: any) => {
                        /**
                         * an error might occurs while
                         * dealing with custom action. We should 
                         * catch that and reject the exception.
                         */
                        try {
                            nsHooks.doAction( 'ns-before-load-order', { order });
                        } catch( exception ) {
                            return reject( exception );
                        }

                        const options   =   this.options.getValue();

                        order = { ...this.defaultOrder(), ...order };
    
                        /**
                         * We'll rebuilt the product
                         */
                        const products  =   [];
                        
                        for( let i = 0; i < order.products.length ; i++ ) {
                            
                            const orderProduct      =   order.products[i];

                            /**
                             * in case the orderProduct is a quick product
                             * we need to fill back the $quantities function
                             */
                            if ( orderProduct.product === null ) {
                                orderProduct.product    =   {
                                    mode: 'custom',
                                    name: orderProduct.name,
                                    unit_id: orderProduct.unit_id,
                                    unit_quantities :   [
                                        await this.defineQuantities( orderProduct )
                                    ]
                                }
                            }

                            orderProduct.$original = () => orderProduct.product;
                            orderProduct.$quantities = () => {
                                let unitQuantity     =   orderProduct
                                    .product
                                    .unit_quantities
                                    .filter(unitQuantity => +unitQuantity.id === +orderProduct.unit_quantity_id || unitQuantity.id === undefined )[0];

                                if ( orderProduct.mode === 'custom' ) {
                                    unitQuantity.custom_price_edit = orderProduct.unit_price; 
                                    unitQuantity.custom_price_with_tax = orderProduct.price_with_tax;
                                    unitQuantity.custom_price_without_tax = orderProduct.price_without_tax;
                                    unitQuantity.custom_price_tax = orderProduct.tax_value;
                                }

                                return unitQuantity;
                            }

                            products.push( orderProduct );
                        }
    
                        /**
                         * we'll redefine the order type
                         */
                        order.type = Object.values(this.types.getValue()).filter((type: any) => type.identifier === order.type)[0];
    
                        /**
                         * the address is provided differently
                         * then we need to rebuild it the way it's saved and used
                         */
                        order.addresses = {
                            shipping: order.shipping_address,
                            billing: order.billing_address
                        }
    
                        delete order.shipping_address;
                        delete order.billing_address;
        
                        /**
                         * let's all set, let's load the order
                         * from now. No further change is required
                         */
    
                        this.buildOrder(order);
                        this.buildProducts(products);

                        await this.selectCustomer(order.customer);

                        resolve(order);
                    }, 
                    error: error => reject(error)
                });
        })
    }

    buildOrder(order) {
        this.order.next(order);
    }

    buildProducts(products) {
        this.recomputeProducts(products);
        this.products.next(products);
        nsHooks.doAction( 'ns-after-cart-changed' );
    }

    printOrderReceipt( order, mode ) {
        const options = this.options.getValue();

        if (options.ns_pos_printing_enabled_for === 'disabled') {
            return false;
        }

        /**
         * There should be a better
         * way of writing this.
         */
        if ( 
            ( options.ns_pos_printing_enabled_for === 'all_orders'  ) ||
            ( options.ns_pos_printing_enabled_for === 'partially_paid_orders' && [ 'paid', 'partially_paid' ].includes( order.payment_status ) ) ||
            ( options.ns_pos_printing_enabled_for === 'only_paid_orders' && [ 'paid' ].includes( order.payment_status ) )
        ) {
            this.print.process( order.id, 'sale', mode );
        } else {
            return false;
        }
    }


    computePaid() {
        const order = this._order.getValue();
        order.tendered = 0;

        if (order.payments.length > 0) {
            order.tendered = order.payments.map(p => p.value).reduce((b, a) => a + b);
        }

        if (order.tendered >= order.total) {
            order.payment_status = 'paid';
        } else if (order.tendered > 0 && order.tendered < order.total) {
            order.payment_status = 'partially_paid';
        }

        order.change = order.tendered - order.total;

        this._order.next(order);
    }

    setPaymentActive( payment: PaymentType ) {
        const payments = this._paymentsType.getValue();
        payments.forEach(p => {
            if ( p.identifier === payment.identifier ) {
                p.selected = true;
            } else {
                p.selected = false;
            }
        });
        this._paymentsType.next(payments);
    }

    definedPaymentsType(payments) {
        this._paymentsType.next(payments);
    }

    selectCustomer(customer) {
        return new Promise((resolve, reject) => {
            const order = this.order.getValue();
            const billing = Object.assign( customer.billing || {},  {});

            if ( billing.id !== undefined ) {
                delete billing.id;
            }

            order.customer = customer;
            order.customer_id = customer.id;
            order.addresses.billing = billing;
            
            this.order.next(order);

            /**
             * asynchronously we can load
             * customer meta data
             */
            if (customer.group === undefined || customer.group === null) {
                nsHttpClient.get(`/api/customers/${customer.id}/group`)
                    .subscribe({
                        next: group => {
                            order.customer.group = group;
                            this.order.next(order);
                            resolve(order);
                        },
                        error: ( error ) => {
                            reject(error);
                        }
                    });
            } else {
                return resolve(order);
            }
        });
    }

    updateCart(current, update) {
        for (let key in update) {
            if (update[key] !== undefined) {
                current[ key ]  =   update[ key ];
            }
        }

        this.order.next(current);

        /**
         * explicitly here we do manually refresh the cart
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
        const order = this.order.getValue();
        const unmatchedConditions = [];

        order.coupons.forEach(coupon => {
            /**
             * by default we'll bypass
             * the product if it's not available
             */
            let isProductValid = true;

            /**
             * if the coupon includes products
             * we make sure the products are included on the cart
             */
            if (coupon.products.length > 0) {
                isProductValid = order.products.filter(product => {
                    return coupon.products.map(p => p.product_id).includes(product.product_id);
                }).length > 0;

                if (!isProductValid && unmatchedConditions.indexOf(coupon) === -1) {
                    unmatchedConditions.push(coupon);
                }
            }

            /**
             * by default we'll bypass
             * the product if it's not available
             */
            let isCategoryValid = true;

            /**
             * if the coupon includes products
             * we make sure the products are included on the cart
             */
            if (coupon.categories.length > 0) {
                isCategoryValid = order.products.filter(product => {
                    return coupon.categories.map(p => p.category_id).includes(product.$original().category_id);
                }).length > 0;

                if (!isCategoryValid && unmatchedConditions.indexOf(coupon) === -1) {
                    unmatchedConditions.push(coupon);
                }
            }
        });

        unmatchedConditions.forEach(coupon => {
            nsSnackBar.error(
                __('The coupons "%s" has been removed from the cart, as it\'s required conditions are no more meet.')
                    .replace('%s', coupon.name),
                __('Okay'), {
                duration: 6000
            }
            ).subscribe();

            this.removeCoupon(coupon);
        });
    }

    async refreshCart() {
        /**
         * check if according to the product
         * available on the cart the coupons must 
         * remains the same.
         */
        this.checkCart();

        const products  = this.products.getValue();
        let order       = this.order.getValue();
        let usePriceWithTax  =   this.options.getValue().ns_pos_price_with_tax;

        const productTotal = products
            .filter( product => product.product_type !== 'dynamic' )
            .map(product => usePriceWithTax === 'yes' ? product.total_price_with_tax : product.total_price_without_tax );

        if (productTotal.length > 0) {
            let productTotalValue       =   productTotal.reduce((b, a) => b + a);
            let dynamicProductValue     =   0;

            let dynamicProducts     =   products
                .filter( product => product.product_type === 'dynamic' )
                .map( product => {
                    product.unit_price      =   ( productTotalValue * product.rate ) / 100;
                    product.total_price     =   product.unit_price * product.quantity;

                    return product.total_price;
                });

            if ( dynamicProducts.length > 0 ) {
                dynamicProductValue     =   dynamicProducts.reduce( (b,a) => b + a );
            }
            
            order.subtotal = productTotalValue + dynamicProductValue;
        } else {
            order.subtotal = 0;
        }

        /**
         * we'll compute here the value
         * of the coupons
         */
        const totalValue = order.coupons.map(customerCoupon => {
            if (customerCoupon.type === 'percentage_discount') {
                customerCoupon.value = (order.subtotal * customerCoupon.discount_value) / 100;
                return customerCoupon.value;
            }

            customerCoupon.value = customerCoupon.discount_value;
            return customerCoupon.value;
        });

        order.total_coupons = 0;

        if (totalValue.length > 0) {
            order.total_coupons = totalValue.reduce((before, after) => before + after);
        }

        if (order.discount_type === 'percentage') {
            order.discount = (order.discount_percentage * order.subtotal) / 100;
        }

        /**
         * if the discount amount is greather
         * than the subtotal, the discount amount
         * will be set to the order.subtotal
         */
        if (order.discount > order.subtotal && order.total_coupons === 0) {
            order.discount = order.subtotal;
            nsSnackBar.info( __( 'The discount has been set to the cart subtotal.' ))
                .subscribe();
        }

        /**
         * save actual change to ensure
         * all listener are up to date.
         */
        order.tax_value         =   0;
        order.total_tax_value   =   0;
        
        this.order.next(order);

        /**
         * will compute the taxes based on 
         * the actual state of the order
         */
        try {
            const response = await this.computeTaxes();
            order = response['data'].order;
        } catch (exception) {
            if (exception !== false && exception.message !== undefined) {
                nsSnackBar.error(exception.message || __('An unexpected error has occurred while fecthing taxes.'), __('OKAY'), { duration: 0 }).subscribe();
            }
        }

        let inclusiveTaxCount   =   0;

        const inclusiveTaxes    =   products.map( (product: OrderProduct) => {
            if ( product.tax_type === 'inclusive' ) {
                return product.tax_value;
            }

            return 0;
        });

        if ( inclusiveTaxes.length > 0 ) {
            inclusiveTaxCount   =   inclusiveTaxes.reduce( ( b, a ) => b + a );
        }

        const taxType   =   order.tax_type;
        const posVat    =   this.options.getValue().ns_pos_vat;

        let tax_value   =   0;

        if (['flat_vat', 'variable_vat', 'products_vat', 'products_flat_vat', 'products_variable_vat'].includes(posVat) ) {
            tax_value   =   order.total_tax_value ;
        }

        if ( taxType === 'exclusive' ) {
            const op1 = math.chain( order.subtotal ).add( order.shipping || 0 ).add( tax_value ).done();
            order.total     =   math.chain( op1 ).subtract( order.discount ).subtract( order.total_coupons ).done();
        } else {
            const op1 = math.chain( order.subtotal ).add( order.shipping || 0 ).done();
            order.total     =   math.chain( op1 ).subtract( order.discount ).subtract( order.total_coupons ).done();
        }

        this.order.next(order);

        nsHooks.doAction('ns-cart-after-refreshed', order);
    }

    /**
     * Get actual stock used by the product
     * using the defined unit
     * @param product_id 
     * @param unit_id 
     */
    getStockUsage(product_id: number, unit_quantity_id: number) {
        const stocks = this._products.getValue().filter((product: OrderProduct) => {
            return product.product_id === product_id && product.unit_quantity_id === unit_quantity_id;
        }).map(product => product.quantity);

        if (stocks.length > 0) {
            return stocks.reduce((b, a) => b + a);
        }

        return 0;
    }

    /**
     * this is resolved when a product is being added to the
     * cart. That will help to mutate the product before 
     * it's added the cart.
     */
    addToCartQueue = [
        ProductUnitPromise,
        ProductQuantityPromise
    ];

    /**
     * Process the item to add it to the cart
     * @param product 
     */
    async addToCart(product) {

        /**
         * This is where all the mutation made by the  
         * queue promises are stored.
         */
        let productData = new Object;

        /**
         * Let's combien the built product
         * with the data resolved by the promises
         */
        let cartProduct: OrderProduct = {
            product_id: product.id || 0,
            name: product.name,
            discount_type: 'percentage',
            discount: 0,
            discount_percentage: 0,
            product_type: product.product_type || 'product',
            rate: product.rate || 0,
            quantity: product.quantity || 0,
            tax_group_id: product.tax_group_id,
            tax_type: product.tax_type || undefined,
            tax_value: 0, // is computed automatically using $original()
            unit_id: product.unit_id || 0,
            unit_price: product.unit_price || 0,
            price_with_tax: product.price_with_tax || 0,
            price_without_tax: product.price_without_tax || 0,
            unit_name: <string>(product.unit_name || ''),
            total_price: 0,
            total_price_without_tax: 0,
            total_price_with_tax: 0,
            mode: product.mode || 'normal',
            $original: product.$original || (() => product),
            $quantities: product.$quantities || undefined
        };

        /**
         * will determin if the 
         * script is processing the add queue
         */
        this._processingAddQueue = true;

        if (cartProduct.product_id !== 0) {
            for (let index in this.addToCartQueue) {

                /**
                 * the popup promise receives the product that
                 * is above to be added. Hopefully as it's passed by reference
                 * updating the product should mutate that once the queue is handled.
                 */
                try {
                    const promiseInstance = new this.addToCartQueue[index](cartProduct);
                    const result = <Object>(await promiseInstance.run(productData));

                    /**
                     * We just mix both to make sure
                     * the mutated value overwrite previously defined values.
                     */
                    productData = { ...productData, ...result };

                } catch (brokenPromise) {
                    /**
                     * if a popup resolve "false",
                     * that means for some reason the Promise has
                     * been broken, therefore we need to stop the queue.
                     */
                    if (brokenPromise === false) {
                        this._processingAddQueue = false;
                        return false;
                    }
                }
            }
        }

        /**
         * end proceesing add queue
         */
        this._processingAddQueue = false;

        /**
         * Let's combien the built product
         * with the data resolved by the promises
         */
        cartProduct = { ...cartProduct, ...productData };

        /**
         * retrieve product that 
         * are currently stored
         */
        const products = this._products.getValue();

        /**
         * we'll check here if the merge feature is enabled
         * If it's the case, we'll have to compare the added product
         * with what already exists and decide to increase the quantity or not.
         */
        if ( this.settings.getValue().ns_pos_items_merge ) {
            const existing      =   products.filter( product => {
                /**
                 * we might check other arguments
                 * in case the products doesn't have the same meta.
                 */
                return (
                    product.product_id === cartProduct.product_id &&
                    product.tax_group_id === cartProduct.tax_group_id &&
                    product.unit_id === cartProduct.unit_id &&
                    product.unit_quantity_id === cartProduct.unit_quantity_id
                );
            });

            if ( existing.length > 0 ) {
                existing[0].quantity       +=  cartProduct.quantity;
            } else {
                /**
                 * push the new product
                 * at the front of the cart
                 */
                products.unshift(cartProduct);
            }

        } else {
            /**
             * push the new product
             * at the front of the cart
             */
            products.unshift(cartProduct);
        }

        /**
         * Once the product has been added to the cart
         * it's being computed
         */
        this.recomputeProducts(products);

        /**
         * dispatch event that the 
         * product has been added.
         */
        this.products.next(products);

        /**
         * when all this has been executed, we can play
         * a sound if it's enabled
         */
        const url     =   this.options.getValue().ns_pos_new_item_audio;

        if ( url.length > 0 ) {
            ( new Audio( url ) ).play();
        }

        nsHooks.doAction( 'ns-after-cart-changed' );
    }

    defineTypes(types) {
        this._types.next(types);
    }

    userCan( permission ) {
        const permissions   =   this._userPermissions.getValue();
        const filtered  =   permissions.filter( (p) => p.namespace === permission );
        return filtered.length > 0;
    }

    async removeProductUsingIndex(index) {
        const products = this._products.getValue();
        const product   =   products[index];

        /**
         * if the product is persistent,
         * we should check on the database if the user is allowed
         * to delete those products.
         */
        if ( product.id ) {
            try {
                await new Promise((resolve, reject) => {
                    const popup = Popup.show( nsPosLoadingPopupVue );
                    nsHttpClient.post(`/api/users/check-permission/`, {
                        permission: 'nexopos.pos.delete-order-product'
                    }).subscribe({
                        next: (response: any) => {
                            popup.close();
                            resolve( response );
                        },
                        error: error => {
                            popup.close();
                            reject( error );
                        }
                    })
                });

                this.resumeRemovingProductUsingIndex( index, products );
            } catch( exception ) {
                nsNotice.error( __( 'Forbidden Action' ), __( 'You are not allowed to remove this product.' ) );
            }
        } else {
            this.resumeRemovingProductUsingIndex( index, products );
        }
    }

    private resumeRemovingProductUsingIndex( index, products ) {
        products.splice(index, 1);
        this.products.next(products);
        nsHooks.doAction( 'ns-after-cart-changed' );
    }

    removeProduct(product) {
        const products = this._products.getValue();
        const index = products.indexOf(product);
        products.splice(index, 1);
        this.products.next(products);
        nsHooks.doAction( 'ns-after-cart-changed' );
    }

    updateProduct(product, data, index = null) {
        const products = this._products.getValue();
        index = index === null ? products.indexOf(product) : index;
        index = index === -1 ? 0 : index;

        /**
         * to ensure Vue updates accordingly.
         */
        products[ index ]       =   { ...product, ...data };

        this.recomputeProducts(products);
        this.products.next(products);
        nsHooks.doAction( 'ns-after-cart-changed' );
    }

    recomputeProducts(products = null) {
        products.forEach( product => {
            this.computeProduct(product);
        });
    }

    getProductUnitPrice( mode, quantities ) {
        switch( mode ) {
            case 'custom':
                return quantities.custom_price_edit;
            case 'normal':
                return quantities.sale_price_edit;
            case 'wholesale':
                return quantities.wholesale_price_edit;
        }
    }

    computeProductTax( product: OrderProduct ) {
        switch( product.mode ) {
            case 'custom':
                return this.computeCustomProductTax( product );
            case 'normal':
                return this.computeNormalProductTax( product );
            case 'wholesale':
                return this.computeWholesaleProductTax( product );
            default: 
                return product;
        }
    }

    private proceedProductTaxComputation( product, price ) {
        const originalProduct   =   product.$original();
        const taxGroup          =   originalProduct.tax_group;

        let price_without_tax   =   this.getProductUnitPrice( product.mode, product.$quantities() );
        let tax_value           =   0;
        let price_with_tax      =   this.getProductUnitPrice( product.mode, product.$quantities() );

        if ( taxGroup !== undefined && taxGroup !== null && taxGroup.taxes !== undefined ) {

            /**
             * get summarize rates
             */
            let summarizedRates     =   0;
            
            if ( taxGroup.taxes.length > 0 ) {
                summarizedRates     =   taxGroup.taxes
                    .map( r => r.rate )
                    .reduce( ( b, a ) => b + a );
            }

            switch( originalProduct.tax_type ) {
                case 'inclusive':
                    price_without_tax   =   this.getPriceWithoutTax( price, summarizedRates, originalProduct.tax_type );
                    price_with_tax      =   price;
                break;
                case 'exclusive':
                    price_without_tax   =   price;
                    price_with_tax      =   this.getPriceWithTax( price, summarizedRates, originalProduct.tax_type );
                break;
            }

            tax_value     =   this.getVatValue( price, summarizedRates, originalProduct.tax_type );
        }
        
        return { price_without_tax, tax_value, price_with_tax };
    }

    computeCustomProductTax( product: OrderProduct ) {
        const originalProduct   =   product.$original();
        const quantities        =   product.$quantities();
        const result            =   this.proceedProductTaxComputation( product, quantities.custom_price_edit );
        
        quantities.custom_price_without_tax =   result.price_without_tax;
        quantities.custom_price_with_tax =   result.price_with_tax;
        quantities.custom_price_tax =   result.tax_value;

        product.$quantities     =   () => {
            return <ProductUnitQuantity>quantities
        }

        return product;
    }

    computeNormalProductTax( product: OrderProduct ) {
        const quantities        =   product.$quantities();
        const result            =   this.proceedProductTaxComputation( product, quantities.sale_price_edit );

        quantities.sale_price_without_tax   =   result.price_without_tax;
        quantities.sale_price_with_tax      =   result.price_with_tax;
        quantities.sale_price_tax           =   result.tax_value;

        product.$quantities     =   () => {
            return <ProductUnitQuantity>quantities
        }

        return product;
    }

    computeWholesaleProductTax( product: OrderProduct ) {
        const quantities        =   product.$quantities();
        const result            =   this.proceedProductTaxComputation( product, quantities.wholesale_price_edit );

        quantities.wholesale_price_without_tax  =   result.price_without_tax;
        quantities.wholesale_price_with_tax     =   result.price_with_tax;
        quantities.wholesale_price_tax          =   result.tax_value;

        product.$quantities     =   () => {
            return <ProductUnitQuantity>quantities
        }

        return product;
    }

    getPrice( quantities, mode, type ) {
        switch( mode ) {
            case 'normal': return quantities[ 'sale_price_' + type ];
            case 'wholesale': return quantities[ 'wholesale_price_' + type ];
            case 'custom': return quantities[ 'custom_price_' + type ];
        } 
    }

    computeProduct(product: OrderProduct) {
        /**
         * determining what is the 
         * real sale price
         */
        if ( product.product_type === 'product' ) {
            if (product.mode === 'normal') {
                product.unit_price = this.getSalePrice(product.$quantities(), product.$original());
                product.tax_value = math.chain( product.$quantities().sale_price_tax ).multiply( product.quantity ).done();
            } else if (product.mode === 'wholesale') {
                product.unit_price = this.getWholesalePrice(product.$quantities(), product.$original());
                product.tax_value = math.chain( product.$quantities().wholesale_price_tax ).multiply( product.quantity ).done();
            } if (product.mode === 'custom') {
                product.unit_price = this.getCustomPrice(product.$quantities(), product.$original());
                product.tax_value = math.chain( product.$quantities().custom_price_tax ).multiply( product.quantity ).done();
            }
        }

        /**
         * computing the discount when it's 
         * based on a percentage. @todo While we believe discount
         * shouldn't be calculated after taxes
         */
        let discount_without_tax:number   =   0;
        let discount_with_tax:number      =   0;
        let price_with_tax:number         =   this.getPrice( product.$quantities(), product.mode, 'with_tax' );
        let price_without_tax:number      =   this.getPrice( product.$quantities(), product.mode, 'without_tax' );

        if (['flat', 'percentage'].includes(product.discount_type)) {
            if (product.discount_type === 'percentage') {
                product.discount        =   math.chain(
                    math.chain(
                        math.chain( product.unit_price ).multiply( product.discount_percentage ).done()
                    ).divide( 100 ).done()
                ).multiply( product.quantity ).done();

                discount_without_tax    =   math.chain(
                    math.chain(
                        math.chain( price_without_tax ).multiply( product.discount_percentage ).done()
                    ).divide( 100 ).done()
                ).multiply( product.quantity ).done();

                discount_with_tax       =   math.chain(
                    math.chain(
                        math.chain( price_with_tax ).multiply( product.discount_percentage ).done()
                    ).divide( 100 ).done()
                ).multiply( product.quantity ).done();

            } else {
                discount_without_tax        =   product.discount;
                discount_with_tax           =   product.discount;
            }
        }

        product.price_with_tax              =   price_with_tax;
        product.price_without_tax           =   price_without_tax;

        product.total_price                 =   math.chain(
            math.chain( product.unit_price ).multiply( product.quantity ).done()
        ).subtract( product.discount ).done();

        product.total_price_with_tax        =   math.chain(
            math.chain( price_with_tax ).multiply( product.quantity ).done()
        ).subtract( discount_with_tax ).done();

        product.total_price_without_tax     =   math.chain(
            math.chain( price_without_tax ).multiply( product.quantity ).done()
        ).subtract( discount_without_tax ).done();

        nsHooks.doAction('ns-after-product-computed', product);
    }

    loadCustomer(id) {
        return nsHttpClient.get(`/api/customers/${id}`);
    }

    defineSettings(settings) {
        this._settings.next(settings);
    }

    voidOrder(order) {
        if (order.id !== undefined) {
            if (['hold'].includes(order.payment_status)) {
                Popup.show(nsConfirmPopup, {
                    title: __( 'Order Deletion' ),
                    message: __( 'The current order will be deleted as no payment has been made so far.' ),
                    onAction: (action) => {
                        if (action) {
                            nsHttpClient.delete(`/api/orders/${order.id}`)
                                .subscribe({
                                    next: (result: any) => {
                                        nsSnackBar.success(result.message).subscribe();
                                        this.reset();
                                    },
                                    error: (error) => {
                                        return nsSnackBar.error(error.message).subscribe();
                                    }
                                });
                        }
                    }
                });
            } else {
                Popup.show( nsPromptPopup, {
                    title: __( 'Void The Order' ),
                    message: __( 'The current order will be void. This will cancel the transaction, but the order won\'t be deleted. Further details about the operation will be tracked on the report. Consider providing the reason of this operation.' ),
                    onAction: (reason) => {
                        if (reason !== false) {
                            nsHttpClient.post(`/api/orders/${order.id}/void`, { reason })
                                .subscribe({
                                    next: (result: any) => {
                                        nsSnackBar.success(result.message).subscribe();
                                        this.reset();
                                    },
                                    error: (error) => {
                                        return nsSnackBar.error(error.message).subscribe();
                                    }
                                })
                        }
                    }
                });
            }
        } else {
            nsSnackBar.error( __( 'Unable to void an unpaid order.' )).subscribe();
        }
    }

    async triggerOrderTypeSelection(selectedType) {
        for (let i = 0; i < this.orderTypeQueue.length; i++) {
            const result = await this.orderTypeQueue[i].promise(selectedType);
        }
    }

    set(key, value) {
        const settings = this.settings.getValue();
        settings[key] = value;
        this.settings.next(settings);
    }

    unset(key) {
        const settings = this.settings.getValue();
        delete settings[key];
        this.settings.next(settings);
    }

    get(key) {
        const settings = this.settings.getValue();
        return settings[key];
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

(window as any).POS = new POS;
(window as any).POSClass = POS;
export const POSInit = <POS>(window as any).POS
