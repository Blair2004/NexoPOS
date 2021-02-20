<template>
    <div class="shadow-lg h-95vh md:h-5/6-screen lg:h-5/6-screen w-95vw md:w-4/6-screen lg:w-3/6-screen bg-white flex flex-col">
        <div class="p-2 border-b flex justify-between items-center">
            <h3 class="font-semibold">{{ __( 'Layaway Parameters' ) }}</h3>
            <div>
                <ns-close-button></ns-close-button>
            </div>
        </div>
        <div class="p-2 flex-auto flex flex-col relative overflow-y-auto">
            <div v-if="fields.length === 0" class="absolute h-full w-full flex items-center justify-center">
                <ns-spinner></ns-spinner>
            </div>
            <div>
                <ns-field v-for="( field, index ) of fields" :field="field" :key="index"></ns-field>
            </div>
            <div class="flex flex-col flex-auto overflow-hidden">
                <div class="border-b border-gray-200">
                    <h3 class="text-2xl flex justify-between py-2 text-gray-700">
                        <span>{{ __( 'Instalments & Payments' ) }}</span>
                        <span>{{ order.total | currency }}</span>
                    </h3>
                    <p class="p-2 mb-2 text-center bg-green-200 text-green-700">
                        {{ __( 'The final payment date must be the last within the instalments.' ) }}
                    </p>
                </div>
                <div class="flex-auto overflow-y-auto">
                    <div class="flex w-full -mx-1 py-2" :key="key" v-for="(instalment, key) of order.instalments">
                        <div class="px-1 w-full md:w-1/2">
                            <ns-field :field="instalment.date"></ns-field>
                        </div>
                        <div class="px-1 w-full md:w-1/2">
                            <ns-field :field="instalment.payment"></ns-field>
                        </div>
                    </div>
                    <div class="my-2" v-if="order.instalments.length === 0">
                        <p class="p-2 bg-gray-200 text-gray-700 text-center">{{ __( 'There is not instalment defined. Please set how many instalments are allowed for this order' ) }}</p>
                    </div>
                </div>
                {{ order.instalments }}
            </div>
        </div>
        <div class="p-2 flex border-t justify-between flex-shrink-0">
            <div></div>
            <div class="-mx-1 flex">
                <div class="px-1">
                    <ns-button @click="close()" type="danger">{{ __( 'Cancel' ) }}</ns-button>
                </div>
                <div class="px-1">
                    <ns-button @click="updateOrder()" type="info">{{ __( 'Save' ) }}</ns-button>
                </div>
            </div>
        </div>
    </div>
</template>
<script>
import FormValidation from '@/libraries/form-validation';
import { nsHttpClient, nsSnackBar } from '@/bootstrap';
import { __ } from '@/libraries/lang';
export default {
    name: 'ns-pos-layaway-popup',
    data() {
        return {
            fields: [],
            instalments: [],
            formValidation: new FormValidation,
            subscription: null
        }
    },
    mounted() {
        this.loadFields();
        this.subscription   =   this.$popup.event.subscribe( action => {
            if ([ 'click-overlay', 'press-esc' ].includes( action.event ) ) {
                this.close();
            }
        });        
    },
    updated() {
        setTimeout( () => {
            document.querySelector( '.is-popup #total_instalments' ).addEventListener( 'change', () => {
                const totalInstalments    =   this.formValidation.extractFields( this.fields ).total_instalments;
                this.generatePaymentFields( totalInstalments );
            });
        }, 200 );
    },
    computed: {
        order() {
            return this.$popupParams.order;
        }
    },
    destroyed() {
        this.subscription.unsubscribe();
    },
    methods: {
        __,
        generatePaymentFields( totalInstalments ) {
            this.order.instalments    =   ( new Array( parseInt( totalInstalments ) ) )
                .fill('')
                .map( _ => {
                    return {
                        date: {
                            type: 'date',
                            name: 'date',
                            label: 'Date',
                            value: '',
                        },
                        payment: {
                            type: 'number',
                            name: 'payment',
                            label: 'Payment',
                            value: '',
                        }
                    }
                });

            this.$forceUpdate();
        },
        close() {
            this.$popupParams.reject({ status: 'failed', message: __( 'You must define layaway settings before proceeding.' ) });
            this.$popup.close();
        },
        updateOrder() {
            this.fields.forEach( field => this.formValidation.validateField( field ) );

            if ( ! this.formValidation.fieldsValid( this.fields ) ) {
                return nsSnackBar.error( __( 'Unable to procee the form is not valid' ) ).subscribe();
            }

            this.$forceUpdate();

            const instalments           =   this.order.instalments.map( instalment => {
                return {
                    payment : instalment.payment.value,
                    date    : instalment.date.value,
                }
            });

            const totalInstalments      =   instalments.reduce( (before, after) => parseFloat( before.payment ) + parseFloat( after.payment ) );

            if ( instalments.filter( instalment => instalment.date === undefined ).length > 0 ) {
                return nsSnackBar.error( __( 'One or more instalments has an invalid date.' ) ).subscribe();
            }

            if ( instalments.filter( instalment => ! ( instalment.payment > 0 ) ).length > 0 ) {
                return nsSnackBar.error( __( 'One or more instalments has an invalid payment.' ) ).subscribe();
            }

            if ( instalments.filter( instalment => moment( instalment.date ).isBefore( ns.date.moment.startOf( 'day' ) ) ).length > 0 ) {
                return nsSnackBar.error( __( 'One or more instalments has a date prior to the current date.' ) ).subscribe();
            }

            if ( totalInstalments != this.order.total ) {
                return nsSnackBar.error( __( 'Total instalments must be equal to the order total.' ) ).subscribe();
            }

            instalments.sort( ( before, after ) => {
                const beforeMoment  =   moment( before.date );
                const afterMoment   =   moment( after.date );

                if ( beforeMoment.isBefore( afterMoment ) ) {
                    return -1;
                } else if ( beforeMoment.isAfter( afterMoment ) ) {
                    return 1;
                }

                return 0;
            });

            const fields                =   this.formValidation.extractFields( this.fields );

            fields.final_payment_date   =   instalments.reverse()[0].date;
            fields.total_instalments    =   instalments.length;

            const order                 =   { ...this.$popupParams.order, ...fields, instalments };
            const { resolve, reject }   =   this.$popupParams;

            this.$popup.close();
            
            return resolve( order );
        },
        loadFields() {
            nsHttpClient.get( '/api/nexopos/v4/fields/ns.layaway' )
                .subscribe( fields => {
                    this.fields     =   this.formValidation.createFields( fields );
                })
        }
    }
}
</script>