<template>
    <div class="shadow-lg w-6/7-screen lg:w-3/5-screen h-6/7-screen lg:h-4/5-screen ns-box overflow-hidden flex flex-col">
        <div class="p-2 border-b ns-box-header text-primary text-center font-medium flex justify-between items-center">
            <div>
                {{ __( 'Previewing :' ) }} {{ product.name }}
            </div>
            <div>
                <ns-close-button @click="popup.close()"></ns-close-button>
            </div>
        </div>
        <div class="flex-auto overflow-y-auto ns-box-body">
            <div class="p-2">
                <ns-tabs :active="active" @active="changeActiveTab( $event )">
                    <ns-tabs-item :label="__( 'Units & Quantities' )" identifier="units-quantities">
                        <table class="table ns-table w-full" v-if="hasLoadedUnitQuantities">
                            <thead>
                                <tr>
                                    <th class="p-1 border">{{ __( 'Unit' ) }}</th>
                                    <th width="150" class="text-right p-1 border">{{ __( 'Sale Price' ) }}</th>
                                    <th width="150" class="text-right p-1 border">{{ __( 'Wholesale Price' ) }}</th>
                                    <th width="150" class="text-right p-1 border">{{ __( 'Quantity' ) }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="unitQuantity of unitQuantities" :key="unitQuantity.id">
                                    <td class="p-1 border text-left">{{ unitQuantity.unit.name }} 
                                        <template v-if="product.rawType === 'materialized' && product.rawStockManagement === 'enabled'">
                                            &mdash; <a @click="convert( unitQuantity, product )" class="text-sm text-info-secondary hover:underline border-dashed" href="javascript:void(0)">{{ __( 'Convert' ) }}</a>
                                        </template>
                                    </td>
                                    <td class="p-1 border text-right">{{ nsCurrency( unitQuantity.sale_price  ) }}</td>
                                    <td class="p-1 border text-right">{{ nsCurrency( unitQuantity.wholesale_price  ) }}</td>
                                    <td class="p-1 border text-right">{{ unitQuantity.quantity }}</td>
                                </tr>
                            </tbody>
                        </table>
                        <ns-spinner v-if="! hasLoadedUnitQuantities" size="16" border="4"></ns-spinner>
                    </ns-tabs-item>
                </ns-tabs>
            </div>
        </div>
    </div>
</template>
<script lang="ts">
import { nsCurrency } from '~/filters/currency';
import { nsHttpClient } from '~/bootstrap';
import { __ } from '~/libraries/lang';
import nsProductsConversion from './ns-products-conversion.vue';

declare const Popup;


export default {
    name: 'ns-products-preview',
    props: [ 'popup', 'product' ],
    computed: {
        product() {
            return this.popup.params.product
        }
    },
    methods: {
        __,
        nsCurrency,
        changeActiveTab( event ) {
            this.active     =   event;

            if ( this.active === 'units-quantities' ) {
                this.loadProductQuantities();
            }
        },
        loadProductQuantities() {
            console.log( 'is loadinfg' );
            this.hasLoadedUnitQuantities            =   false;
            nsHttpClient.get( `/api/products/${this.product.id}/units/quantities` )
                .subscribe({
                    next: result => {
                        this.unitQuantities             =   result;
                        this.hasLoadedUnitQuantities    =   true;
                    }
                })
        },
        async convert( unitQuantity, product ) {
            try {
                const promise   =   await new Promise( ( resolve, reject ) => {
                    Popup.show( nsProductsConversion, {
                        unitQuantity,
                        product,
                        resolve, 
                        reject
                    })
                });

                this.loadProductQuantities();
            } catch( exception ) {
                console.log({ exception })
            }
        }
    },
    data() {
        return {
            active : 'units-quantities',
            unitQuantities: [],
            hasLoadedUnitQuantities: false
        }
    },
    mounted() {
        this.loadProductQuantities();
    }
}
</script>