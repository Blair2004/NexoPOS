<template>
    <div class="h-full w-full py-2">
        <div class="px-2 pb-2" v-if="order">
            <!-- Order Summary -->
            <div class="grid grid-cols-2 gap-2 mb-4">
                <div class="h-16 flex justify-between items-center border elevation-surface info text-xl md:text-3xl p-2">
                    <span>{{ __( 'Total' ) }}:</span>
                    <span>{{ nsCurrency( order.total ) }}</span>
                </div>
                <div class="h-16 flex justify-between items-center border elevation-surface success text-xl md:text-3xl p-2">
                    <span>{{ __( 'Paid' ) }}:</span>
                    <span>{{ nsCurrency( order.tendered ) }}</span>
                </div>
            </div>

            <!-- Loading State -->
            <div v-if="loading" class="flex flex-col items-center justify-center py-8">
                <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-500 mb-4"></div>
                <p class="text-gray-600">{{ __( 'Generating payment link...' ) }}</p>
            </div>

            <!-- Payment Link Generated -->
            <div v-else-if="paymentUrl" class="flex flex-col items-center py-4">
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-6 w-full max-w-md text-center">
                    <i class="las la-wallet text-5xl text-blue-600 mb-3"></i>
                    <h3 class="text-lg font-bold mb-2">{{ __( 'Crypto Payment Ready' ) }}</h3>
                    <p class="text-sm text-gray-600 mb-4">
                        {{ __( 'Share this link with the customer or scan the QR code to pay.' ) }}
                    </p>

                    <!-- Payment Link -->
                    <div class="bg-white border rounded p-3 mb-4 break-all text-xs font-mono text-left select-all cursor-pointer"
                         @click="copyLink">
                        {{ paymentUrl }}
                    </div>

                    <div class="flex gap-2 justify-center mb-4">
                        <button @click="copyLink"
                                class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded text-sm">
                            <i class="las la-copy mr-1"></i>
                            {{ copied ? __( 'Copied!' ) : __( 'Copy Link' ) }}
                        </button>
                        <a :href="paymentUrl" target="_blank"
                           class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded text-sm inline-flex items-center">
                            <i class="las la-external-link-alt mr-1"></i>
                            {{ __( 'Open' ) }}
                        </a>
                    </div>

                    <!-- Chain Badge -->
                    <div class="flex justify-center gap-2">
                        <span class="bg-yellow-100 text-yellow-800 px-2 py-1 rounded text-xs font-medium">
                            {{ chainLabel }}
                        </span>
                        <span class="bg-gray-100 text-gray-600 px-2 py-1 rounded text-xs">
                            {{ __( 'Waiting for confirmation...' ) }}
                        </span>
                    </div>
                </div>

                <!-- Mark as Paid Button -->
                <button @click="makeFullPayment"
                        class="mt-4 bg-success-secondary hover:bg-green-600 text-white px-8 py-3 rounded text-lg font-medium w-full max-w-md">
                    <i class="las la-check-circle mr-2"></i>
                    {{ __( 'Confirm Payment Received' ) }}
                </button>
            </div>

            <!-- Error State -->
            <div v-else-if="error" class="flex flex-col items-center py-8">
                <div class="bg-red-50 border border-red-200 rounded-lg p-6 w-full max-w-md text-center">
                    <i class="las la-exclamation-circle text-5xl text-red-500 mb-3"></i>
                    <h3 class="text-lg font-bold text-red-700 mb-2">{{ __( 'Payment Error' ) }}</h3>
                    <p class="text-sm text-red-600 mb-4">{{ error }}</p>
                    <button @click="generateLink"
                            class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded text-sm">
                        <i class="las la-redo-alt mr-1"></i>
                        {{ __( 'Retry' ) }}
                    </button>
                </div>
            </div>

            <!-- Initial State -->
            <div v-else class="flex flex-col items-center py-8">
                <button @click="generateLink"
                        class="bg-blue-600 hover:bg-blue-700 text-white px-8 py-4 rounded-lg text-xl font-medium">
                    <i class="las la-wallet mr-2"></i>
                    {{ __( 'Generate Crypto Payment Link' ) }}
                </button>
                <p class="text-sm text-gray-500 mt-3">
                    {{ __( 'A PayTheFly payment link will be created for this order.' ) }}
                </p>
            </div>
        </div>
    </div>
</template>

<script>
import { Popup } from '~/libraries/popup';
import nsPosConfirmPopupVue from '~/popups/ns-pos-confirm-popup.vue';
import { __ } from '~/libraries/lang';
import { nsSnackBar, nsHttpClient } from '~/bootstrap';
import { nsCurrency } from '~/filters/currency';

export default {
    name: 'paythefly-payment',
    props: [ 'label', 'identifier' ],
    data() {
        return {
            order: null,
            orderSubscription: null,
            paymentUrl: null,
            loading: false,
            error: null,
            copied: false,
            pollTimer: null,
            chainLabel: 'BSC',
        };
    },
    mounted() {
        this.orderSubscription = POS.order.subscribe( order => {
            this.order = order;
        });

        // Auto-generate payment link when component mounts
        if ( this.order && this.order.id ) {
            this.generateLink();
        }
    },
    unmounted() {
        if ( this.orderSubscription ) {
            this.orderSubscription.unsubscribe();
        }
        if ( this.pollTimer ) {
            clearInterval( this.pollTimer );
        }
    },
    methods: {
        __,
        nsCurrency,

        async generateLink() {
            if ( ! this.order || ! this.order.id ) {
                this.error = __( 'Order must be saved before generating a crypto payment link.' );
                return;
            }

            this.loading = true;
            this.error = null;
            this.paymentUrl = null;

            try {
                const response = await nsHttpClient.get( `/api/paythefly/orders/${this.order.id}/payment-url` );
                if ( response.data.status === 'success' ) {
                    this.paymentUrl = response.data.payment_url;
                    this.startPolling();
                } else {
                    this.error = response.data.message || __( 'Unknown error occurred.' );
                }
            } catch ( err ) {
                this.error = err.response?.data?.message || err.message || __( 'Failed to generate payment link.' );
            } finally {
                this.loading = false;
            }
        },

        async copyLink() {
            if ( ! this.paymentUrl ) return;
            try {
                await navigator.clipboard.writeText( this.paymentUrl );
                this.copied = true;
                setTimeout( () => { this.copied = false; }, 2000 );
                nsSnackBar.success( __( 'Payment link copied to clipboard.' ) );
            } catch {
                // Fallback for older browsers
                const textarea = document.createElement( 'textarea' );
                textarea.value = this.paymentUrl;
                document.body.appendChild( textarea );
                textarea.select();
                document.execCommand( 'copy' );
                document.body.removeChild( textarea );
                this.copied = true;
                setTimeout( () => { this.copied = false; }, 2000 );
            }
        },

        startPolling() {
            if ( this.pollTimer ) clearInterval( this.pollTimer );

            this.pollTimer = setInterval( async () => {
                try {
                    const response = await nsHttpClient.get( `/api/paythefly/orders/${this.order.id}/status` );
                    if ( response.data.confirmed ) {
                        clearInterval( this.pollTimer );
                        nsSnackBar.success( __( 'Payment confirmed on-chain!' ) );

                        // Auto-submit the payment
                        POS.addPayment({
                            value: this.order.total - this.order.tendered,
                            identifier: this.identifier,
                            selected: false,
                            label: this.label,
                            readonly: true,
                        });

                        this.$emit( 'submit' );
                    }
                } catch {
                    // Silently continue polling
                }
            }, 5000 ); // Poll every 5 seconds
        },

        makeFullPayment() {
            Popup.show( nsPosConfirmPopupVue, {
                title: __( 'Confirm Crypto Payment' ),
                message: __( 'Confirm that the crypto payment of {total} has been received via PayTheFly?' )
                    .replace( '{total}', nsCurrency( this.order.total ) ),
                onAction: ( confirmed ) => {
                    if ( confirmed ) {
                        POS.addPayment({
                            value: this.order.total - this.order.tendered,
                            identifier: this.identifier,
                            selected: false,
                            label: this.label,
                            readonly: true,
                        });
                        this.$emit( 'submit' );
                    }
                },
            });
        },
    },
};
</script>
