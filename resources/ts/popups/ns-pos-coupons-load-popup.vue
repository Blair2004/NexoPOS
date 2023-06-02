<template>
    <div class="shadow-lg ns-box w-95vw md:w-3/6-screen lg:w-2/6-screen">
        <div class="border-b ns-box-header p-2 flex justify-between items-center">
            <h3 class="font-bold">{{ __( 'Load Coupon' ) }}</h3>
            <div>
                <ns-close-button @click="closePopup()"></ns-close-button>
            </div>
        </div>
        <div class="p-1 ns-box-body">
            <ns-tabs @active="setActiveTab( $event )" :active="activeTab">
                <ns-tabs-item 
                    :label="__( 'Apply A Coupon' )" 
                    padding="p-2"
                    identifier="apply-coupon">
                    <div class="border-2 input-group info rounded flex">
                        <input ref="coupon" @keyup.enter="loadCoupon()" v-model="couponCode" type="text" class="coupon-field w-full text-primary p-2 outline-none" :placeholder="placeHolder">
                        <button @click="loadCoupon()" class="px-3 py-2">{{ __( 'Load' ) }}</button>
                    </div>
                    <div class="pt-2">
                        <ns-notice color="info">
                            <template v-slot:description>{{ __( 'Input the coupon code that should apply to the POS. If a coupon is issued for a customer, that customer must be selected priorly.' ) }}</template>
                        </ns-notice>
                    </div>
                    <div class="pt-2 flex" v-if="order && order.customer_id === undefined">
                        <button @click="selectCustomer()"  class="w-full border p-2 outline-none ns-numpad-key info cursor-pointer text-center">{{ __( 'Click here to choose a customer.' ) }}</button>
                    </div>
                    <div class="pt-2" v-if="order && order.customer_id !== undefined">
                        <ns-notice color="success">
                            <template v-slot:description>{{ __( 'Loading Coupon For : ' ) + `${order.customer.first_name} ${order.customer.last_name}` }}</template>
                        </ns-notice>                        
                    </div>
                    <div class="overflow-hidden">
                        <div class="pt-2 fade-in-entrance anim-duration-500 overflow-y-auto ns-scrollbar h-64" v-if="coupon">
                            <table class="w-full ns-table">
                                <tbody>
                                    <tr>
                                        <td class="p-2 w-1/2 border">{{ __( 'Coupon Name' ) }}</td>
                                        <td class="p-2 w-1/2 border">{{ coupon.name }}</td>
                                    </tr>                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                               
                                    <tr>
                                        <td class="p-2 w-1/2 border">{{ __( 'Discount' ) }} ({{ getCouponType( coupon.type ) }})</td>
                                        <td class="p-2 w-1/2 border">{{ getDiscountValue( coupon ) }}</td>
                                    </tr>
                                    <tr>
                                        <td class="p-2 w-1/2 border">{{ __( 'Usage' ) }}</td>
                                        <td class="p-2 w-1/2 border">{{ ( coupon.customer_coupon.length > 0 ? coupon.customer_coupon[0].usage : 0 ) + '/' + ( coupon.limit_usage || __( 'Unlimited' ) ) }}</td>
                                    </tr>
                                    <tr>
                                        <td class="p-2 w-1/2 border">{{ __( 'Valid From' ) }}</td>
                                        <td class="p-2 w-1/2 border">{{ coupon.valid_hours_start || __( 'N/A' ) }}</td>
                                    </tr>
                                    <tr>
                                        <td class="p-2 w-1/2 border">{{ __( 'Valid Till' ) }}</td>
                                        <td class="p-2 w-1/2 border">{{ coupon.valid_hours_end || __( 'N/A' ) }}</td>
                                    </tr>
                                    <tr>
                                        <td class="p-2 w-1/2 border">{{ __( 'Categories' ) }}</td>
                                        <td class="p-2 w-1/2 border">
                                            <ul>
                                                <li class="rounded-full px-3 py-1 border" :key="category.id" v-for="category of coupon.categories">{{ category.category.name }}</li>
                                                <li v-if="coupon.categories.length === 0">{{ __( 'Not applicable' ) }}</li>
                                            </ul>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="p-2 w-1/2 border">{{ __( 'Products' ) }}</td>
                                        <td class="p-2 w-1/2 border">
                                            <ul>
                                                <li class="rounded-full px-3 py-1 border" :key="product.id" v-for="product of coupon.products">{{ product.product.name }}</li>
                                                <li v-if="coupon.products.length === 0">{{ __( 'Not applicable' ) }}</li>
                                            </ul>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </ns-tabs-item>
                <ns-tabs-item :label="__( 'Active Coupons' )" padding="p-1" identifier="active-coupons">
                    <ul v-if="order">
                        <li v-for="( coupon, index) of order.coupons" :key="index" class="flex justify-between elevation-surface border items-center px-2 py-1">
                            <div class="flex-auto">
                                <h3 class="font-semibold text-primary p-2 flex justify-between">
                                    <span>{{ coupon.name }}</span>
                                    <span>{{ getDiscountValue( coupon ) }}</span>
                                </h3>
                            </div>
                            <div>
                                <ns-close-button @click="removeCoupon( index )"></ns-close-button>
                            </div>
                        </li>
                        <li v-if="order.coupons.length === 0" class="flex justify-between elevation-surface border items-center p-2">
                            {{ __( 'No coupons applies to the cart.' ) }}
                        </li>
                    </ul>
                </ns-tabs-item>
            </ns-tabs>
        </div>
        <div class="flex" v-if="coupon">
            <button @click="apply()" class="w-1/2 px-3 py-2 bg-success-tertiary text-white font-bold">
                {{ __( 'Apply' ) }}
            </button>
            <button @click="cancel()" class="w-1/2 px-3 py-2 bg-error-tertiary text-white font-bold">
                {{ __( 'Cancel' ) }}
            </button>
        </div>
    </div>
</template>
<script>
import popupCloser from '~/libraries/popup-closer';
import popupResolver from '~/libraries/popup-resolver';
import { nsHttpClient, nsSnackBar } from '~/bootstrap';
import { __ } from "~/libraries/lang";
import nsPosCustomerSelectPopupVue from './ns-pos-customer-select-popup.vue';
import nsNotice from '~/components/ns-notice.vue';
import { ref } from "vue";

export default {
    name: 'ns-pos-coupons-load-popup',
    props: [ 'popup' ],
    components: {
        nsNotice
    },
    data() {
        return {
            placeHolder: __( 'Coupon Code' ),
            couponCode: null,
            order: null,
            activeTab: 'apply-coupon',
            orderSubscriber: null,
            coupon: null
        }
    },
    mounted() {
        this.popupCloser();

        this.orderSubscriber    =   POS.order.subscribe( order => {
            this.order = ref(order);
            
            if ( this.order.coupons.length > 0 ) {
                this.activeTab  =   'active-coupons';
            }
        });

        if ( this.popup.params ) {

            /**
             * We want to add a way to quickly
             * apply a coupon while loading the popup
             */
            if ( this.popup.params.apply_coupon ) {
                this.couponCode     =   this.popup.params.apply_coupon;
                this.getCoupon( this.couponCode )
                    .subscribe({
                        next: ( coupon ) => {
                            this.coupon     =   coupon;
                            this.apply();
                        }
                    })
            }
        }
    },
    unmounted() {
        this.orderSubscriber.unsubscribe();
    },
    methods: {
        __,
        popupCloser,
        popupResolver,

        selectCustomer() {
            Popup.show( nsPosCustomerSelectPopupVue );
        },

        cancel() {
            this.coupon         =   null;
            this.couponCode     =   null;
        },

        removeCoupon( index ) {
            this.order.coupons.splice( index, 1 );
            POS.refreshCart();
        },

        apply() {
            try {
                if ( 
                    this.coupon.valid_hours_start !== null &&
                    ! ns.date.moment.isAfter( this.coupon.valid_hours_start ) && 
                    this.coupon.valid_hours_start.length > 0
                ) {
                    return nsSnackBar.error( __( 'The coupon is out from validity date range.' ) )
                        .subscribe();
                }

                if ( 
                    this.coupon.valid_hours_end !== null &&
                    ! ns.date.moment.isBefore( this.coupon.valid_hours_end ) &&
                    this.coupon.valid_hours_end.length > 0 
                ) {
                    return nsSnackBar.error( __( 'The coupon is out from validity date range.' ) )
                        .subscribe();
                }

                const requiredProducts      =   this.coupon.products;

                if (
                    requiredProducts.length > 0
                ) {
                    const productIds    =   requiredProducts.map( p => p.product_id );
                    if ( this.order.products.filter( p => productIds.includes( p.product_id ) ).length === 0 ) {
                        return nsSnackBar.error( __( 'This coupon requires products that aren\'t available on the cart at the moment.' ) )
                            .subscribe();
                    }
                }

                const requiredCategories      =   this.coupon.categories;

                if (
                    requiredCategories.length > 0
                ) {
                    const categoriesIds    =   requiredCategories.map( p => p.category_id );
                    if ( this.order.products.filter( p => categoriesIds.includes( p.$original().category_id ) ).length === 0 ) {
                        return nsSnackBar.error( __( 'This coupon requires products that belongs to specific categories that aren\'t included at the moment.' ).replace( '%s', ) )
                            .subscribe();
                    }
                }

                let finalCoupon    =   {
                    customer_coupon_id   :   this.coupon.customer_coupon.length > 0 ? this.coupon.customer_coupon[0].id : 0,
                    minimum_cart_value: this.coupon.minimum_cart_value,
                    maximum_cart_value: this.coupon.maximum_cart_value,
                    name: this.coupon.name,
                    type: this.coupon.type,
                    value: 0,
                    coupon_id: this.coupon.id,
                    limit_usage: this.coupon.limit_usage,
                    code: this.coupon.code,
                    discount_value: this.coupon.discount_value,
                    categories: this.coupon.categories,
                    products: this.coupon.products,
                }

                this.cancel();

                POS.pushCoupon( finalCoupon );
                this.activeTab  =   'active-coupons';

                setTimeout( () => {
                    this.popupResolver( finalCoupon );
                }, 500 );

                nsSnackBar.success( __( 'The coupon has applied to the cart.' ) )
                    .subscribe();

            } catch( exception ) {
                // unable to push the coupon
                console.log( exception );
            }
        },

        getCouponType( type ) {
            switch( type ) {
                case 'percentage_discount':
                    return __( 'Percentage' );
                case 'flat_discount':
                    return __( 'Flat' );
                default:
                    return __( 'Unknown Type' );
            }
        },

        getDiscountValue( coupon ) {
            switch( coupon.type ) {
                case 'percentage_discount': return coupon.discount_value + '%';
                case 'flat_discount': return this.$options.filters.currency( coupon.discount_value );
            }
        },

        closePopup() {
            this.popupResolver( false );
        },

        setActiveTab( tab ) {
            this.activeTab  =   tab;

            if ( tab === 'apply-coupon' ) {
                setTimeout( () => {
                    document.querySelector( '.coupon-field' ).select();
                }, 10 );
            }
        },

        getCoupon( code ) {
            if ( ! this.order.customer_id > 0 ) {
                return nsSnackBar.error( __( 'You must select a customer before applying a coupon.' ) );
            }

            return nsHttpClient.post( `/api/customers/coupons/${code}`, {
                    customer_id :  this.order.customer_id
                });
        },

        loadCoupon() {
            const code      =   this.couponCode;

            this.getCoupon( code )
                .subscribe({
                    next: coupon => {
                        this.coupon     =   coupon;
                        nsSnackBar.success( __( 'The coupon has been loaded.' ) ).subscribe()
                    },
                    error : error => {
                        nsSnackBar.error( error.message || __( 'An unexpected error occurred.' ) ).subscribe()
                    }
                });
        }
    }
}
</script>
