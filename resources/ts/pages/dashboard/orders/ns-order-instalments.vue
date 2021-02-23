<template>
    <div class="-mx-4 flex-auto flex flex-wrap">
        <div class="flex flex-auto">
            <div class="w-full mb-2 flex-wrap">
                <div class="w-full mb-2 px-4">
                    <h3 class="font-semibold text-gray-800 pb-2 border-b border-blue-400">{{ __( 'Instalments' ) }}</h3>
                </div>
                <div class="px-4">
                    <ul>
                        <li :class="instalment.paid ? 'bg-green-200 border-green-400' : 'bg-gray-200 border-blue-400'"
                            class="border-b border-l flex justify-between" 
                            :key="instalment.id" 
                            v-for="instalment of instalments">
                            <span class="p-2">
                                <span v-if="! instalment.date_clicked" @click="toggleDateEdition( instalment )">{{ instalment.date }}</span>
                                <span v-if="instalment.date_clicked"><input 
                                    @blur="toggleDateEdition( instalment )"
                                    v-model="instalment.date"
                                    type="date" ref="date" class="border border-blue-400 rounded"></span>
                            </span>
                            <div class="flex items-center">
                                <span :class="instalment.paid ? 'border-green-400' : 'border-blue-400'" class="flex items-center px-2 h-full border-r">
                                    <span 
                                        v-if="! instalment.price_clicked" 
                                        @click="togglePriceEdition( instalment )">{{ instalment.amount | currency }}</span>
                                    <span v-if="instalment.price_clicked">
                                        <input ref="amount" 
                                            v-model="instalment.amount"
                                            @blur="togglePriceEdition( instalment )" type="text" class="border border-blue-400 p-1">
                                    </span>
                                </span>
                                <span v-if="!instalment.paid" :class="instalment.paid ? 'border-green-400' : 'border-blue-400'" class="w-28 justify-center flex items-center px-2 h-full border-r">
                                    <button @click="updateInstalment( instalment )" :class="instalment.paid ? 'bg-gray-400 cursor-not-allowed' : 'bg-blue-400'" class="rounded-full px-3 py-1 text-white">{{ __( 'Update' ) }}</button>
                                </span>
                                <span v-if="instalment.paid" class="w-28 border-green-400 justify-center flex items-center px-2 h-full border-r">
                                    {{ __( 'Paid' ) }}
                                </span>
                            </div>
                        </li>
                        <li class="flex justify-end p-2 bg-gray-200 border-r border-b border-l border-gray-400">
                            <div class="-mx-2 flex flex-wrap items-center">
                                <span class="px-2">
                                    {{ order.total | currency }}
                                </span>
                                <span class="px-2">
                                    <button @click="addInstalment()" class="rounded-full px-3 py-1 bg-blue-400 text-white">{{ __( 'Add Instalment' ) }}</button>
                                </span>
                            </div>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</template>
<script>
import Labels from "@/libraries/labels";
import { __ } from '@/libraries/lang';
import { nsHttpClient, nsSnackBar } from '@/bootstrap';
import nsPosConfirmPopupVue from '@/popups/ns-pos-confirm-popup.vue';
export default {
    props: [ 'order' ],
    name: 'ns-order-instalments',
    data() {
        return {
            labels: new Labels,
            original: [],
            instalments: []
        }
    },
    mounted() {
        this.loadInstalments();
    },
    methods: {
        __,
        loadInstalments() {
            nsHttpClient.get( `/api/nexopos/v4/orders/${this.order.id}/instalments` )
                .subscribe( instalments => {
                    this.original       =   instalments;
                    this.instalments    =   instalments.map( instalment => {
                        instalment.price_clicked    =   false;
                        instalment.date_clicked     =   false;
                        instalment.date             =   moment( instalment.date ).format( 'YYYY-MM-DD' );
                        return instalment;
                    });
                })
        },
        togglePriceEdition( instalment ) {
            if ( ! instalment.paid ) {
                instalment.price_clicked = ! instalment.price_clicked;
                this.$forceUpdate();
                if ( instalment.price_clicked ) {
                    setTimeout( () => {
                        this.$refs[ 'amount' ][0].select();
                    }, 100 );
                } 
            }
        },
        updateInstalment( instalment ) {
            // const index             =   this.instalments.indexOf( instalment );
            // const totalInstalment   =   this.instalments
            //     .map( instalment => parseFloat( instalments.amount ) )
            //     .reduce( ( before, after ) => before + after );

            // if ( totalInstalment !== this.order.total ) {
            //     this.instalments[ index ].amount    =   parseFloat( this.original[ index ].amount );
            //     nsSnackBar.error( __( 'The defined amount'))
            // }
            Popup.show( nsPosConfirmPopupVue, {
                title: __( 'Confirm Your Action' ),
                message: __( 'Would you like to update that instalment ?' ),
                onAction: action => {
                    if ( action ) {
                        nsHttpClient.post( `/api/nexopos/v4/orders/${this.order.id}/instalments/${instalment.id}`, { instalment })
                            .subscribe( result => {
                                nsSnackBar.success( result.message ).subscribe();
                            }, error => {
                                nsSnackBar.error( error.message || __( 'An unexpected error has occured' ) ).subscribe();
                            })
                    }
                }
            })
        },
        toggleDateEdition( instalment ) {
            if ( ! instalment.paid ) {
                instalment.date_clicked = ! instalment.date_clicked;
                this.$forceUpdate();
                if ( instalment.date_clicked ) {
                    setTimeout( () => {
                        this.$refs[ 'date' ][0].select();
                    }, 200 );
                }
            }
        }
    }
}
</script>