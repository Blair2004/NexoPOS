<template>
    <div class="-mx-4 flex-auto flex flex-wrap">
        <div class="flex flex-auto">
            <div class="w-full mb-2 flex-wrap">
                <div class="w-full mb-2 px-4">
                    <h3 class="font-semibold text-gray-800 pb-2 border-b border-blue-400">{{ __( 'Instalments' ) }}</h3>
                </div>
                <div class="px-4">
                    <ul class="border-gray-400 border-t text-gray-700">
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
                                <div v-if="!instalment.paid && instalment.id" :class="instalment.paid ? 'border-green-400' : 'border-blue-400'" class="w-36 justify-center flex items-center px-2 h-full border-r">
                                    <div class="px-2">
                                        <ns-icon-button buttonClass="bg-green-400 hover:bg-green-500 text-white hover:text-white hover:border-green-600" @click="markAsPaid( instalment )" className="la-money-bill-wave-alt"></ns-icon-button>
                                    </div>
                                    <div class="px-2">
                                        <ns-icon-button buttonClass="bg-blue-400 hover:bg-blue-500 text-white hover:text-white hover:border-blue-600" @click="updateInstalment( instalment )" className="la-save"></ns-icon-button>
                                    </div>
                                    <div class="px-2">
                                        <ns-icon-button buttonClass="bg-red-400 text-white hover:border hover:border-blue-400 hover:bg-red-500" @click="deleteInstalment( instalment )" className="la-trash-alt"></ns-icon-button>
                                    </div>
                                </div>
                                <div v-if="!instalment.paid && !instalment.id" :class="instalment.paid ? 'border-green-400' : 'border-blue-400'" class="w-36 justify-center flex items-center px-2 h-full border-r">
                                    <div class="px-2">
                                        <button @click="createInstalment( instalment )" class="px-3 py-1 rounded-full bg-blue-400 text-white">
                                            <i class="las la-plus"></i>
                                            {{ __( 'Create' ) }}
                                        </button>
                                    </div>
                                </div>
                                <span v-if="instalment.paid" class="w-36 border-green-400 justify-center flex items-center px-2 h-full border-r">
                                    <button @click="showReceipt( instalment )" class="px-3 text-xs py-1 rounded-full bg-blue-400 text-white">
                                        <i class="las la-print"></i>
                                        {{ __( 'Receipt' ) }}
                                    </button>
                                </span>
                            </div>
                        </li>
                        <li class="flex justify-between p-2 bg-gray-200 border-r border-b border-l border-gray-400">
                            <div class="flex items-center justify-center">
                                <span>
                                    {{ __( 'Total :' ) }} {{ order.total | currency }}
                                </span>
                                <span class="ml-1 text-sm">
                                    ({{ __( 'Remaining :' ) }} {{ order.total - totalInstalments | currency }})
                                </span>
                            </div>
                            <div class="-mx-2 flex flex-wrap items-center">
                                <span class="px-2">
                                    {{ __( 'Instalments:' ) }} {{ totalInstalments | currency }}
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
import nsPosOrderInstalmentsPayment from '@/pages/dashboard/orders/ns-order-instalments-payment.vue';
import Print from '@/libraries/print';

export default {
    props: [ 'order' ],
    name: 'ns-order-instalments',
    data() {
        return {
            labels: new Labels,
            original: [],
            instalments: [],
            print: new Print({ settings: systemSettings, options: systemOptions, type: 'payment' }),
        }
    },
    mounted() {
        this.loadInstalments();
    },
    computed: {
        totalInstalments() {
            if ( this.instalments.length > 0 ) {
                return this.instalments
                    .map( instalment => instalment.amount )
                    .reduce( ( before, after ) => parseFloat( before ) + parseFloat( after ) );
            }
            return 0;
        }
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
        showReceipt( instalment ) {
            if ( instalment.payment_id === null ) {
                return nsSnackBar.error( __( 'This instalment doesn\'t have any payment attached.' ) ).subscribe();
            }

            this.print.printOrder( instalment.payment_id );
        },
        addInstalment() {
            this.instalments.push({
                date: ns.date.moment.format( 'YYYY-MM-DD' ),
                amount: this.order.total - this.totalInstalments,
                paid: false
            });
        },
        createInstalment( instalment ) {
            Popup.show( nsPosConfirmPopupVue, {
                title: __( 'Confirm Your Action' ),
                message: __( 'Would you like to create this instalment ?' ),
                onAction: action => {
                    if ( action ) {
                        nsHttpClient.post( `/api/nexopos/v4/orders/${this.order.id}/instalments`, { instalment })
                            .subscribe({
                                next: result => {
                                    this.loadInstalments();
                                    nsSnackBar.success( result.message ).subscribe();
                                },
                                error: error => {
                                    nsSnackBar.error( error.message || __( 'An unexpected error has occured' ) ).subscribe();
                                }
                            })
                    }
                }
            })
        },
        deleteInstalment( instalment ) {
            Popup.show( nsPosConfirmPopupVue, {
                title: __( 'Confirm Your Action' ),
                message: __( 'Would you like to delete this instalment ?' ),
                onAction: action => {
                    if ( action ) {
                        nsHttpClient.delete( `/api/nexopos/v4/orders/${this.order.id}/instalments/${instalment.id}` )
                            .subscribe({
                                next: result => {
                                    const index     =   this.instalments.indexOf( instalment );
                                    this.instalments.splice( index, 1 );
                                    nsSnackBar.success( result.message ).subscribe();
                                },
                                error: error => {
                                    nsSnackBar.error( error.message || __( 'An unexpected error has occured' ) ).subscribe();
                                }
                            })
                    }
                }
            })
        },
        async markAsPaid( instalment ) {
            try {
                const response  =   await new Promise( ( resolve, reject ) => {
                    Popup.show( nsPosOrderInstalmentsPayment, {
                        order : this.order,
                        instalment,
                        resolve, 
                        reject
                    });
                });

                this.loadInstalments();
            } catch ( exception ) {
                // ...
            }
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
            Popup.show( nsPosConfirmPopupVue, {
                title: __( 'Confirm Your Action' ),
                message: __( 'Would you like to update that instalment ?' ),
                onAction: action => {
                    if ( action ) {
                        nsHttpClient.put( `/api/nexopos/v4/orders/${this.order.id}/instalments/${instalment.id}`, { instalment })
                            .subscribe({
                                next: result => {
                                    nsSnackBar.success( result.message ).subscribe();
                                },
                                error: error => {
                                    nsSnackBar.error( error.message || __( 'An unexpected error has occured' ) ).subscribe();
                                }
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