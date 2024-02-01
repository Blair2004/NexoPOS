<template>
    <div class="shadow-lg w-6/7-screen lg:w-3/5-screen ns-box overflow-hidden flex flex-col">
        <template v-if="unitQuantities.length > 0">
            <div class="p-2 border-b ns-box-header text-primary text-center font-medium flex justify-between items-center">
                <div>
                    {{ __( 'Unit Conversion : {product}' ).replace( '{product}', product.name ) }}
                </div>
                <div>
                    <ns-close-button @click="popup.close()"></ns-close-button>
                </div>
            </div>
            <div class="border-b border-box-edge">
                <div class="flex">
                    <div class="p-2 w-full md:w-1/2 flex flex-col items-center justify-center" @click="setVisible( unitPair, 'from' )" :class="unitPair.from.selected ? 'bg-info-primary text-white' : ''">
                        <span>{{ unitPair.from.unit.name }}</span>
                        <h3 class="font-bold text-3xl">
                            {{ unitPair.from.quantity }}
                        </h3>
                    </div>
                    <div class="border-r border-box-edge relative">
                        <div class="rounded-full h-12 w-12 flex items-center justify-center absolute shadow bg-input-button p-2" @click="switchPair()" style="position: absolute;left: -22px;    top: 14px;">
                            <i class="las la-exchange-alt  text-3xl"></i>
                        </div>
                    </div>
                    <div class="p-2 w-full md:w-1/2 flex flex-col items-center justify-center" @click="setVisible( unitPair, 'to' )" :class="unitPair.to.selected ? 'bg-info-primary text-white' : ''">
                        <span>{{ unitPair.to.unit.name }}</span>
                        <h3 class="font-bold text-3xl">
                            {{ Math.floor( unitPair.to.quantity ) }}
                        </h3>
                    </div>
                </div>
            </div>
            <div class="p-2 border-b border-box-edge">
                <ns-field :field="field" v-for="field of selectedUnitPair().fields"></ns-field>
            </div>
            <div class="">
                <ns-numpad @changed="updateFromPairQuantity( $event )">

                </ns-numpad>
            </div>
        </template>
        <div class="flex items-center h-full justify-center" v-if="unitQuantities.length === 0">
            <ns-spinner></ns-spinner>
        </div>
    </div>
</template>

<script lang="ts">
import { nsNotice } from '~/bootstrap';
import { __ } from '~/libraries/lang';

export default {
    name: 'ns-products-convertion',
    props: [ 'popup', 'unitQuantity', 'product' ],
    data() {
        return {
            unitQuantities: [],
            unitPair: {
                from: {
                    unit: {},
                    unitQuantity: {},
                    selected: true,
                    quantity: 0,
                    fields: [
                        {
                            label: __( 'Assigned Unit' ),
                            name: 'assigned_unit',
                            value: '',
                            type: 'select',
                            options: [],
                        }
                    ],
                },
                to: {
                    unit: {},
                    unitQuantity: {},
                    selected: false,
                    quantity: 0,
                    fields: [
                        {
                            label: __( 'Assigned Unit' ),
                            name: 'assigned_unit',
                            value: '',
                            type: 'select',
                            options: [],
                        }
                    ],
                }
            },
            selected: ''
        }
    },
    mounted() {
        this.loadProductQuantities();
        console.log( this );
    },
    methods: {
        __,
        updateFromPairQuantity( quantity ) {
            if ( quantity.length === 0 ) {
                quantity =  0;
            }

            /**
             * if the quantity exceed the limit
             * let's set it to the limit
             */


            this.unitPair.from.quantity    =   quantity;

            // update conversion quantity
            const baseUnit  =   this.unitQuantities.filter( unitQuantity => unitQuantity.unit.base_unit )[0];

            console.log( baseUnit )

            if ( baseUnit.length === 0 ) {
                return nsNotice.error(
                    __( 'An error occured' ),
                    __( 'The product {product} has no base unit' ).replace( '{product}', this.product.name )
                );
            }

            const baseUnitValue     =   ( this.unitPair.from.quantity * this.unitPair.from.unit.value ) * baseUnit.unit.value;
            const destinationUnitValue     =   baseUnitValue / this.unitPair.to.unit.value;
            console.log({ destinationUnitValue })
            this.unitPair.to.quantity      =   destinationUnitValue;
        },
        switchPair() {
            const fromUnit  =   this.unitPair.from.unit;
            const toUnit    =   this.unitPair.to.unit;

            this.unitPair.from.unit     =   toUnit;
            this.unitPair.to.unit       =   fromUnit;
        },
        selectedUnitPair() {
            return this.unitPair[ this.unitPair.from.selected ? 'from' : 'to' ];
        },
        setVisible( unitPair, direction ) {
            const otherPair     =   direction === 'from' ? 'to' : 'from';
            unitPair[ direction ].selected  =   true;
            unitPair[ otherPair ].selected  =   false;
        },
        loadProductQuantities() {
            nsHttpClient.get( '/api/products/' + this.unitQuantity.product_id + '/units/quantities' ).subscribe({
                next: unitQuantities => {
                    this.unitQuantities                 =   unitQuantities;
                    this.unitPair.from.unit             =   this.unitQuantity.unit;
                    this.unitPair.from.unitQuantity     =   this.unitQuantity;
                    this.unitPair.to.unit               =   unitQuantities.filter( unitQuantity => unitQuantity.unit.id !== this.unitQuantity.unit.id )[0].unit;
                    console.log( unitQuantities );
                }
            })
        },
    }
}
</script>
