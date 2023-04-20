<script>
import { nsCurrency } from '~/filters/currency';
import { nsHttpClient } from '~/bootstrap';
import { __ } from '~/libraries/lang';

export default {
    data() {
        return {
            products: [],
            isLoading: false,
        }
    },
    props: [ 'popup' ],
    computed: {
        order() {
            return this.popup.params.order;
        }
    },
    mounted() {
        this.loadProducts();
    },
    methods: {
        __,
        nsCurrency,
        close() {
            this.popup.params.reject( false );
            this.popup.close();
        },
        loadProducts() {
            this.isLoading  =   true;
            const id    =   this.popup.params.order.id;

            nsHttpClient.get( `/api/orders/${id}/products` )
                .subscribe( result => {
                    this.isLoading  =   false;
                    this.products   =   result;
                })
        },
        openOrder() {
            this.popup.close();
            this.popup.params.resolve( this.order );
        }
    }
}
</script>
<template>
    <div class="shadow-lg ns-box w-6/7-screen md:w-3/5-screen lg:w-2/5-screen h-6/7-screen flex flex-col overflow-hidden">
        <div class="p-2 flex justify-between text-primary items-center border-b ns-box-header">
            <h3 class="font-semibold">{{ __( 'Products' ) }} &mdash; {{ order.code }} <span v-if="order.title">({{ order.title }})</span></h3>
            <div>
                <ns-close-button @click="close()"></ns-close-button>
            </div>
        </div>
        <div class="flex-auto p-2 overflow-y-auto ns-box-body">
            <div class="flex-auto relative" v-if="isLoading">
                <div class="h-full w-full flex items-center justify-center">
                    <ns-spinner></ns-spinner>
                </div>
            </div>
            <template v-if="! isLoading">
                <div class="item" v-for="product of products" :key="product.id">
                    <div class="flex-col border-b border-info-primary py-2">
                        <div class="title font-semibold text-primary flex justify-between">
                            <span>{{ product.name }} (x{{ product.quantity }})</span>
                            <span>{{ nsCurrency( price ) }}</span>
                        </div>
                        <div class="text-sm text-primary">
                            <ul>
                                <li>{{ __( 'Unit' ) }} : {{ product.unit.name }}</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </template>
        </div>
        <div class="flex justify-end p-2 border-t ns-box-footer">
            <div class="px-1">
                <div class="-mx-2 flex">
                    <div class="px-1">
                        <ns-button @click="openOrder()" type="info">{{ __( 'Open' ) }}</ns-button>
                    </div>
                    <div class="px-1">
                        <ns-button @click="close()" type="error">{{ __( 'Close' ) }}</ns-button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>