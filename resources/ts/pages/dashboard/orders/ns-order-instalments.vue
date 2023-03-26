<template>
    <div class="-mx-4 flex-auto flex flex-wrap">
        <div class="flex flex-auto">
            <div class="w-full mb-2 flex-wrap">
                <div class="w-full mb-2 px-4">
                    <h3 class="font-semibold text-secondary pb-2 border-b border-info-primary">{{ __( 'Instalments' ) }}</h3>
                </div>
                <div class="px-4">
                    <ul class="border-table-th-edge border-t text-primary">
                        <li :class="instalment.paid ? 'success' : 'info'"
                            class="border-b border-l flex justify-between elevation-surface"
                            :key="instalment.id"
                            v-for="instalment of instalments">
                            <span class="p-2">
                                <span v-if="! instalment.date_clicked" @click="toggleDateEdition( instalment )">{{ instalment.date }}</span>
                                <span v-if="instalment.date_clicked"><input
                                    @blur="toggleDateEdition( instalment )"
                                    v-model="instalment.date"
                                    type="date" ref="date" class="border border-info-primary rounded"></span>
                            </span>
                            <div class="flex items-center">
                                <div class="flex items-center px-2 h-full border-r">
                                    <span 
                                        v-if="! instalment.price_clicked" 
                                        @click="togglePriceEdition( instalment )">{{ nsCurrency( instalment.amount ) }}</span>
                                    <span v-if="instalment.price_clicked">
                                        <input ref="amount"
                                            v-model="instalment.amount"
                                            @blur="togglePriceEdition( instalment )" type="text" class="border border-info-primary p-1">
                                    </span>
                                </div>
                                <div v-if="!instalment.paid && instalment.id" class="w-36 justify-center flex items-center px-2 h-full border-r">
                                    <div class="px-2">
                                        <ns-icon-button type="success" @click="markAsPaid( instalment )" className="la-money-bill-wave-alt"></ns-icon-button>
                                    </div>
                                    <div class="px-2">
                                        <ns-icon-button type="info" @click="updateInstalment( instalment )" className="la-save"></ns-icon-button>
                                    </div>
                                    <div class="px-2">
                                        <ns-icon-button type="error" @click="deleteInstalment( instalment )" className="la-trash-alt"></ns-icon-button>
                                    </div>
                                </div>
                                <div v-if="!instalment.paid && !instalment.id" class="w-36 justify-center flex items-center px-2 h-full border-r">
                                    <div class="px-2">
                                        <div class="ns-button info">
                                            <button @click="createInstalment( instalment )" class="px-3 py-1 rounded-full">
                                                <i class="las la-plus"></i>
                                                {{ __( 'Create' ) }}
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                <div v-if="instalment.paid" class="w-36 justify-center flex items-center px-2 h-full">
                                    <div class="ns-button info">
                                        <button @click="showReceipt( instalment )" class="px-3 text-xs py-1 rounded-full">
                                            <i class="las la-print"></i>
                                            {{ __( 'Receipt' ) }}
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </li>
                        <li class="flex justify-between p-2 border-r border-b border-l elevation-surface">
                            <div class="flex items-center justify-center">
                                <span>
                                    {{ __( 'Total :' ) }} {{ nsCurrency( order.total ) }}
                                </span>
                                <span class="ml-1 text-sm">
                                    ({{ __( 'Remaining :' ) }} {{ nsCurrency( order.total - totalInstalments ) }})
                                </span>
                            </div>
                            <div class="-mx-2 flex flex-wrap items-center">
                                <span class="px-2">
                                    {{ __( 'Instalments:' ) }} {{ nsCurrency( totalInstalments ) }}
                                </span>
                                <span class="px-2">
                                    <div class="ns-button info">
                                        <button @click="addInstalment()" class="rounded-full px-3 py-1">{{ __( 'Add Instalment' ) }}</button>
                                    </div>
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
import Labels from "~/libraries/labels";
import { __ } from '~/libraries/lang';
import { nsHttpClient, nsSnackBar } from '~/bootstrap';
import nsPosConfirmPopupVue from '~/popups/ns-pos-confirm-popup.vue';
import nsPosOrderInstalmentsPayment from '~/pages/dashboard/orders/ns-order-instalments-payment.vue';
import Print from '~/libraries/print';
import { nsCurrency } from '~/filters/currency';

export default {
    props: [ 'order' ],
    name: 'ns-order-instalments',
    data() {
        return {
            labels: new Labels,
            original: [],
            instalments: [],
            print: new Print({ urls: systemUrls, options: systemOptions, type: 'payment' }),
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
        nsCurrency,
        loadInstalments() {
            nsHttpClient.get( `/api/orders/${this.order.id}/instalments` )
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

            this.print.process( instalment.payment_id, 'payment' );
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
                        nsHttpClient.post( `/api/orders/${this.order.id}/instalments`, { instalment })
                            .subscribe({
                                next: result => {
                                    this.loadInstalments();
                                    nsSnackBar.success( result.message ).subscribe();
                                },
                                error: error => {
                                    nsSnackBar.error( error.message || __( 'An unexpected error has occurred' ) ).subscribe();
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
                        nsHttpClient.delete( `/api/orders/${this.order.id}/instalments/${instalment.id}` )
                            .subscribe({
                                next: result => {
                                    const index     =   this.instalments.indexOf( instalment );
                                    this.instalments.splice( index, 1 );
                                    nsSnackBar.success( result.message ).subscribe();
                                },
                                error: error => {
                                    nsSnackBar.error( error.message || __( 'An unexpected error has occurred' ) ).subscribe();
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
                        nsHttpClient.put( `/api/orders/${this.order.id}/instalments/${instalment.id}`, { instalment })
                            .subscribe({
                                next: result => {
                                    nsSnackBar.success( result.message ).subscribe();
                                },
                                error: error => {
                                    nsSnackBar.error( error.message || __( 'An unexpected error has occurred' ) ).subscribe();
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
