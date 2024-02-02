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
            <div class="relative">
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
                    <ns-field @change="handlePairUnitUpdated( $event )" :field="field" v-for="field of selectedUnitPair().fields"></ns-field>
                </div>
                <div class="">
                    <ns-numpad @next="submitConvertion()" :value="unitPair.from.quantity" @changed="updateFromPairQuantity( $event )">
                        <template v-slot:numpad-footer>
                            <div @click="updateFromPairQuantity( unitPair.from.unitQuantity.quantity )" class="w-full ns-numpad-key h-24 font-bold flex items-center justify-center cursor-pointer col-span-3">
                                {{ __( 'Convert {quantity} available' ).replace( '{quantity}', unitPair.from.unitQuantity.quantity ) }}
                            </div>
                        </template>
                    </ns-numpad>
                </div>
                <div v-if="isLoading" class="top-0 left-0 absolute h-full w-full flex items-center justify-center" style="background: rgb(121 121 121 / 20%)">
                    <ns-spinner size="24"></ns-spinner>
                </div>
            </div>
        </template>
        <div class="flex items-center h-full justify-center" v-if="unitQuantities.length === 0">
            <ns-spinner></ns-spinner>
        </div>
    </div>
</template>

<script lang="ts">
import { nsNotice, nsSnackBar } from '~/bootstrap';
import { nsConfirmPopup } from '~/components/components';
import { __ } from '~/libraries/lang';
import { Popup } from '~/libraries/popup';

export default {
    name: 'ns-products-convertion',
    props: [ 'popup', 'unitQuantity', 'product' ],
    data() {
        return {
            unitQuantities: [],
            isLoading: false,
            unitPair: {
                from: {
                    unit: {},
                    unitQuantity: {},
                    selected: true,
                    quantity: 0,
                    realQuantity: 0,
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
        async submitConvertion() {
            if ( this.unitPair.from.quantity === 0 ) {
                return nsSnackBar.error( __( 'The quantity should be greater than 0' ) ).subscribe();
            }

            if ( Math.floor( this.unitPair.to.quantity ) === 0 ) {
                return nsSnackBar.error( 
                    __( 'The provided quantity can\'t result in any convertion for unit "{destination}"' )
                        .replace( '{destination}', this.unitPair.to.unit.name )
                ).subscribe();
            }

            if ( this.unitPair.from.quantity !== this.unitPair.from.realQuantity ) {
                try {
                    const result    =   await new Promise( ( resolve, reject ) => {
                        Popup.show( nsConfirmPopup, {
                            title: __( 'Conversion Warning' ),
                            message: __( 'Only {quantity}({source}) can be converted to {destinationCount}({destination}). Would you like to proceed ?' )
                                .replace( '{quantity}', this.unitPair.from.realQuantity )
                                .replace( '{destinationCount}', Math.floor( this.unitPair.to.quantity ) )
                                .replace( '{source}', this.unitPair.from.unit.name )
                                .replace( '{destination}', this.unitPair.to.unit.name ),
                            onAction: ( action ) => {
                                if ( action ) {
                                    resolve( true );
                                } else {
                                    reject( false );
                                }
                            }
                        });
                    });

                    return this.proceedConversionSubmissions();
                } catch( e ) {
                    return; // something failed on the popup. We'll stop here
                }
            }

            try {
                const result    =   await new Promise( ( resolve, reject ) => {
                    Popup.show( nsConfirmPopup, {
                        title: __( 'Confirm Conversion' ),
                        message: __( 'You\'re about to convert {quantity}({source}) to {destinationCount}({destination}). Would you like to proceed?' )
                            .replace( '{quantity}', this.unitPair.from.quantity )
                            .replace( '{destinationCount}', Math.floor( this.unitPair.to.quantity ) )
                            .replace( '{source}', this.unitPair.from.unit.name )
                            .replace( '{destination}', this.unitPair.to.unit.name ),
                        onAction: ( action ) => {
                            if ( action ) {
                                resolve( true );
                            } else {
                                reject( false );
                            }
                        }
                    });
                });

                return this.proceedConversionSubmissions();
            } catch( e ) {
                return; // something failed on the popup. We'll stop here
            }
        },
        proceedConversionSubmissions() {
            this.isLoading  =   true;

            nsHttpClient.post( `/api/products/${this.unitQuantity.product_id}/units/conversion`, {
                from: this.unitPair.from.unit.id,
                to: this.unitPair.to.unit.id,
                quantity: this.unitPair.from.realQuantity
            }).subscribe({
                next: result => {
                    this.isLoading  =   false;
                    this.popup.close();

                    this.popup.params.resolve( result );

                    return nsNotice.success(
                        __( 'Conversion Successful' ),
                        __( 'The product {product} has been converted successfully.' ).replace( '{product}', this.product.name )
                    );
                },
                error: error => {
                    this.isLoading  =   false;

                    this.popup.params.reject( error );
                    return nsNotice.error( 
                        __( 'An error occured' ), 
                        error.message || __( 'An error occured while converting the product {product}' ).replace( '{product}', this.product.name ) 
                    );
                }
            })
        },
        handlePairUnitUpdated( event ) {
            const selectedUnitPair              =   this.selectedUnitPair();
            selectedUnitPair.unitQuantity       =   this.unitQuantities.filter( unitQuantity => unitQuantity.unit.id === event.value )[0];
            selectedUnitPair.unit               =   selectedUnitPair.unitQuantity.unit;
            selectedUnitPair.fields[0].value    =   event.value;

            /**
             * if by the end the other unit part has the same unit
             * we should change it to any other unit
             */
            const otherUnitPair     =   selectedUnitPair === this.unitPair.from ? this.unitPair.to : this.unitPair.from;

            if ( otherUnitPair.unit.id === selectedUnitPair.unit.id ) {
                otherUnitPair.unitQuantity      =   this.unitQuantities.filter( unitQuantity => unitQuantity.unit.id !== selectedUnitPair.unit.id )[0];
                otherUnitPair.unit              =   otherUnitPair.unitQuantity.unit;
                otherUnitPair.fields[0].value   =   otherUnitPair.unit.id;
            }

            this.updateFromPairQuantity( this.unitPair.from.quantity );
        },
        updateFromPairQuantity( quantity ) {
            if ( quantity.length === 0 ) {
                quantity =  0;
            }

            /**
             * if the quantity exceed the limit
             * let's set it to the limit
             */
            if ( quantity > this.unitPair.from.unitQuantity.quantity ) {
                quantity    =   this.unitPair.from.unitQuantity.quantity;
                nsSnackBar.info( __( 'The quantity has been set to the maximum available' ) ).subscribe();
            }

            this.unitPair.from.quantity    =   parseFloat( quantity );

            // update conversion quantity
            const baseUnit  =   this.unitQuantities.filter( unitQuantity => unitQuantity.unit.base_unit )[0];

            if ( baseUnit.length === 0 ) {
                return nsNotice.error(
                    __( 'An error occured' ),
                    __( 'The product {product} has no base unit' ).replace( '{product}', this.product.name )
                );
            }

            const baseUnitValue     =   ( this.unitPair.from.quantity * this.unitPair.from.unit.value ) * baseUnit.unit.value;
            const destinationUnitValue     =   baseUnitValue / this.unitPair.to.unit.value;

            if ( this.unitPair.from.unit.value < this.unitPair.to.unit.value ) {
                this.unitPair.from.realQuantity    =   Math.floor( destinationUnitValue ) * this.unitPair.to.unit.value;
            } else {
                this.unitPair.from.realQuantity     =   this.unitPair.from.quantity;
            }
            
            this.unitPair.to.quantity      =   destinationUnitValue;
        },
        switchPair() {
            const fromUnit  =   this.unitPair.from.unit;
            const toUnit    =   this.unitPair.to.unit;

            this.unitPair.from.unit         =   toUnit;
            this.unitPair.from.unitQuantity =   this.unitQuantities.filter( unitQuantity => unitQuantity.unit.id === toUnit.id )[0];
            this.unitPair.from.quantity     =   Math.floor( this.unitPair.to.quantity );

            this.unitPair.to.unit           =   fromUnit;
            this.unitPair.to.unitQuantity   =   this.unitQuantities.filter( unitQuantity => unitQuantity.unit.id === fromUnit.id )[0];

            this.updateFromPairQuantity( this.unitPair.from.quantity );
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
                    this.unitPair.from.fields[0].value  =   this.unitQuantity.unit.id;
                    this.unitPair.from.fields[0].options    =   unitQuantities.map( unitQuantity => {
                        return {
                            label: unitQuantity.unit.name,
                            value: unitQuantity.unit.id
                        }
                    });

                    this.unitPair.to.unit               =   unitQuantities.filter( unitQuantity => unitQuantity.unit.id !== this.unitQuantity.unit.id )[0].unit;
                    this.unitPair.to.unitQuantity       =   unitQuantities.filter( unitQuantity => unitQuantity.unit.id !== this.unitQuantity.unit.id )[0];
                    this.unitPair.to.fields[0].value    =   this.unitPair.to.unit.id;
                    this.unitPair.to.fields[0].options  =   unitQuantities.map( unitQuantity => {
                        return {
                            label: unitQuantity.unit.name,
                            value: unitQuantity.unit.id
                        }
                    });
                }
            })
        },
    }
}
</script>
