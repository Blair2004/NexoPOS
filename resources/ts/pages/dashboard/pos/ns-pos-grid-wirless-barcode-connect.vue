<template>
    <div class="shadow-lg w-[75.71vw] md:w-[51.43vw] lg:w-[40.14vw] ns-box flex flex-col">
        <div class="p-2 border-b ns-box-header flex justify-between items-center">
            <h3 class="text-fontcolor">{{ __('Connect Wireless Barcode Scanner') }}</h3>
            <div class="flex items-center">
                <ns-close-button @click="close()"></ns-close-button>
            </div>
        </div>
        
        <div class="p-6 ns-box-body flex-auto overflow-y-auto">
            <ns-tabs :active="activeTab" @changeTab="onTabChange($event)">
                <ns-tabs-item identifier="scan" :label="__( 'Scan QR Code' )" class="overflow-hidden">
                    <div class="text-center">
                        <p class="text-fontcolor mb-4">
                            {{ __('Scan the QR code below with your application to establish a wireless connection.') }}
                        </p>

                        <div class="flex justify-center mb-6">
                            <div class="bg-white p-4 rounded-lg shadow-sm border border-box-edge">
                                <template v-if="state && state.socket_status === 'connected'">
                                    <canvas ref="qrcodePlaceholder" class="w-64 h-64 bg-gray-200 flex items-center justify-center text-gray-500 rounded"></canvas>
                                </template>
                                <div v-else class="w-64 h-64 bg-gray-200 flex items-center justify-center text-gray-500 rounded">
                                    <ns-spinner size="10" class="text-blue-500"></ns-spinner>
                                </div>
                            </div>
                        </div>

                        <div class="flex flex-col gap-3 max-w-xs mx-auto">
                            <div class="border border-input-edge bg-input-background rounded-lg flex">
                                <div class="p-2 flex-auto">{{ trimmedQrCode }}</div>
                                <button @click="copyQRCodeValue()" class="flex items-center bg-input-button m-[2px] hover:bg-input-button/50 w-[40px] justify-center">
                                    <i class="las la-copy"></i>
                                </button>
                            </div>
                            <div v-if="state.socket_status !== 'connected'" class="flex items-center justify-center gap-2 p-3 rounded-lg bg-input-background border border-input-edge">
                                <div class="w-3 h-3 rounded-full bg-warning-primary animate-pulse"></div>
                                <span class="text-fontcolor font-medium">{{ __( "Connecting..." ) }}</span>
                            </div>
                        </div>
                    </div>
                </ns-tabs-item>

                <ns-tabs-item identifier="clients" :label="__( 'Connected Clients' )">
                    <div class="flex flex-col gap-5">
                        <p class="text-fontcolor text-center">
                            {{ __('See the host and any connected clients currently linked to this wireless barcode session.') }}
                        </p>

                        <section class="flex flex-col gap-3">
                            <h4 class="text-fontcolor font-semibold text-sm uppercase tracking-wide">
                                {{ __('Host') }}
                            </h4>
                            <div v-for="client in state.clients.filter( client => client.role === 'host' )" class="p-4 rounded-lg border border-box-edge bg-input-background flex items-center justify-between gap-4">
                                <div class="min-w-0">
                                    <div class="flex items-center gap-2">
                                        <span class="text-fontcolor font-semibold truncate">{{ client.display_name }}</span>
                                        <span class="text-xs px-2 py-1 rounded-full bg-blue-500/10 text-blue-600">
                                            {{ client.role }}
                                        </span>
                                    </div>
                                    <div class="text-sm text-fontcolor-soft truncate mt-1">
                                        {{ client.socket_id }}
                                    </div>
                                </div>

                                <div class="flex items-center gap-2 shrink-0">
                                    <div :class="client.status === 'connected' ? 'bg-success-tertiary' : 'bg-warning-tertiary'" class="w-2.5 h-2.5 rounded-full"></div>
                                </div>
                            </div>
                        </section>

                        <section class="flex flex-col gap-3">
                            <h4 class="text-fontcolor font-semibold text-sm uppercase tracking-wide">
                                {{ __('Connected Clients') }}
                            </h4>

                            <div class="grid gap-3">
                                <div
                                    v-for="client in state.clients.filter( client => client.role !== 'host' )"
                                    :key="client.participant_id"
                                    class="p-4 rounded-lg border border-box-edge bg-input-background flex items-center justify-between gap-4"
                                >
                                    <div class="min-w-0">
                                        <div class="flex items-center gap-2">
                                            <span class="text-fontcolor font-semibold truncate">{{ client.display_name }}</span>
                                            <span class="text-xs px-2 py-1 rounded-full bg-success-primary/10 text-success-primary">
                                                {{ client.role }}
                                            </span>
                                        </div>
                                        <div class="text-sm text-fontcolor-soft truncate mt-1">
                                            {{ client.socket_id }}
                                        </div>
                                    </div>

                                    <div class="flex items-center gap-2 shrink-0">
                                        <div :class="client.status === 'connected' ? 'bg-success-tertiary' : 'bg-warning-tertiary'" class="w-2.5 h-2.5 rounded-full"></div>
                                    </div>
                                </div>
                            </div>

                            <div v-if="connectedClients.length === 0" class="p-4 rounded-lg border border-dashed border-box-edge text-center text-fontcolor-soft">
                                {{ __('No connected clients yet.') }}
                            </div>
                        </section>
                    </div>
                </ns-tabs-item>
            </ns-tabs>
        </div>
        
        <div class="p-2 border-t ns-box-footer flex justify-end gap-2">
            <ns-button @click="stopSocket()" type="error">
                {{ __('Stop Connexion') }}
            </ns-button>
        </div>
    </div>
</template>

<script lang="ts">
import qrcode from 'qrcode';
import { nsSnackBar } from '~/bootstrap';
import { nsConfirmPopup } from '~/components/components';
import popupCloser from '~/libraries/popup-closer';
import popupResolver from '~/libraries/popup-resolver';

declare const __;
declare const POS;
declare const Popup;

export default {
    name: 'NsPosGridWirelessBarcodeConnect',
    props: ['popup'],
    
    mounted() {
        this.popupCloser();
        this.connectToSocket();

        this.wirelessStateSubscriber = POS.wirelessBarcodeState.subscribe((state) => {
            this.state = state;

            console.log({ state })

            if (this.activeTab === 'scan') {
                setTimeout(() => {
                    this.renderQRcode();
                }, 100);
            }
        });
    },
    beforeUnmount() {
        this.wirelessStateSubscriber?.unsubscribe();
    },

    data() {
        return {
            activeTab: 'scan',
            state: {
                connected: false,
                clients: [],
            },
            qrCodeDataUrl: '',
            wirelessStateSubscriber: null,
        }
    },

    computed: {
        hostClient() {
            return [];
        },
        connectedClients() {
            return [];
        },
        trimmedQrCode() {
            const code = this.getQRCodeValue();

            return code.length < 20 ? code : code.substring(0,20) + '...';
        }
    },
    
    methods: {
        __,
        popupResolver,
        popupCloser,

        stopSocket(){
            Popup.show( nsConfirmPopup, {
                title: __( 'Confirm Your Action' ),
                message: __( 'Would you like to close the connexion to the wireless barcode?' ),
                onAction: ( action ) => {
                    if ( action ) {
                        POS.stopWirelessBarcodeChannel();
                        this.popup.close();
                    }
                }
            })
        },
        encodeQRCode( code ) {
            return btoa(unescape(encodeURIComponent(code)))
                .replace(/\+/g, '-')
                .replace(/\//g, '_')
                .replace(/=+$/, '');
        },
        getQRCodeValue() {
            if (!this.state.channel) {
                return "";
            }

            return this.encodeQRCode( JSON.stringify(this.state.channel) );
        },
        async copyQRCodeValue() {
            const qrValue = this.getQRCodeValue();

            if (!qrValue) {
                return;
            }

            try {
                if (navigator.clipboard && window.isSecureContext) {
                    await navigator.clipboard.writeText(qrValue);
                } else {
                    this.copyWithTextarea(qrValue);
                }

                nsSnackBar.success( __( 'The QR code was copied' ) );

            } catch (error) {
                console.error("Error copying QR code value:", error);
            }
        },
        copyWithTextarea( value ) {
            const textarea = document.createElement("textarea");
            textarea.value = value;
            textarea.setAttribute("readonly", "");
            textarea.style.position = "absolute";
            textarea.style.left = "-9999px";
            document.body.appendChild(textarea);
            textarea.select();
            document.execCommand("copy");
            document.body.removeChild(textarea);
        },
        renderQRcode() {
            const element = this.$refs.qrcodePlaceholder as HTMLDivElement;

            if (element) {
                /**
                 * to avoid any camera to quickly see what's value is on the string
                 * we need to encode that.
                 */
                const qrValue = this.getQRCodeValue();

                qrcode.toCanvas(element, qrValue, { width: 256 }, (error) => {
                    if (error) {
                        console.error('Error generating QR code:', error);
                    }
                });
            } else {
                console.error('QR code placeholder element not found.');
            }
        },
        close() {
            if (this.popup.params.reject) {
                this.popup.params.reject(false);
            }
            this.popup.close();
        },
        connectToSocket() {
            // ...
        },
        onTabChange(tabIdentifier: string) {
            this.activeTab = tabIdentifier;


            if (tabIdentifier === 'scan') {
                this.$nextTick(() => this.renderQRcode());
            } else {
                POS.getChannelClients( this.state.channel.channel_uuid );
            }
        }
    }
};
</script>
