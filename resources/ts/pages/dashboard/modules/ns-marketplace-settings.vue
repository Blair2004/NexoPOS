<template>
    <div class="ns-box w-95vw max-w-xl overflow-hidden rounded-lg border border-box-edge bg-box-background text-fontcolor shadow-xl">
        <div class="ns-box-header flex items-center justify-between border-b border-box-edge p-4">
            <div class="flex items-center gap-3">
                <span class="flex h-10 w-10 items-center justify-center rounded-lg bg-primary/10 text-primary">
                    <i class="las la-cog text-xl" aria-hidden="true"></i>
                </span>
                <div>
                    <h3 class="font-semibold">{{ __( 'Marketplace Settings' ) }}</h3>
                    <p class="text-xs text-fontcolor-soft">{{ __( 'Manage marketplace data and this installation connection.' ) }}</p>
                </div>
            </div>
            <ns-close-button @click="close()"></ns-close-button>
        </div>

        <div class="space-y-3 p-4">
            <button
                type="button"
                class="flex w-full items-center gap-4 rounded-lg border border-box-edge bg-background p-4 text-left transition-colors hover:border-primary/30 hover:bg-box-elevation-hover disabled:cursor-not-allowed disabled:opacity-60"
                :disabled="isRefreshing || isDisconnecting"
                @click="refreshCache()">
                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-info-primary text-info-tertiary">
                    <i class="las la-sync text-xl" :class="isRefreshing ? 'animate-spin' : ''" aria-hidden="true"></i>
                </span>
                <span class="min-w-0 flex-1">
                    <span class="block text-sm font-semibold">{{ isRefreshing ? __( 'Clearing marketplace cache...' ) : __( 'Clear marketplace cache' ) }}</span>
                    <span class="mt-1 block text-xs leading-5 text-fontcolor-soft">{{ __( 'Discard cached module and category results, then fetch the latest marketplace data.' ) }}</span>
                </span>
            </button>

            <button
                v-if="isConnected"
                type="button"
                class="flex w-full items-center gap-4 rounded-lg border border-error-secondary bg-background p-4 text-left transition-colors hover:bg-error-primary disabled:cursor-not-allowed disabled:opacity-60"
                :disabled="isRefreshing || isDisconnecting"
                @click="disconnect()">
                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-error-primary text-error-tertiary">
                    <i class="las la-unlink text-xl" aria-hidden="true"></i>
                </span>
                <span class="min-w-0 flex-1">
                    <span class="block text-sm font-semibold text-error-tertiary">{{ isDisconnecting ? __( 'Disconnecting...' ) : __( 'Disconnect from My NexoPOS' ) }}</span>
                    <span class="mt-1 block text-xs leading-5 text-fontcolor-soft">{{ __( 'Revoke this installation and remove its locally stored connection credentials.' ) }}</span>
                </span>
            </button>

            <a
                v-else
                :href="authorizationUrl"
                class="flex w-full items-center gap-4 rounded-lg border border-box-edge bg-background p-4 text-left transition-colors hover:border-primary/30 hover:bg-box-elevation-hover">
                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-success-primary text-success-tertiary">
                    <i class="las la-link text-xl" aria-hidden="true"></i>
                </span>
                <span class="min-w-0 flex-1">
                    <span class="block text-sm font-semibold">{{ __( 'Connect to My NexoPOS' ) }}</span>
                    <span class="mt-1 block text-xs leading-5 text-fontcolor-soft">{{ __( 'Connect this installation to access licenses, downloads, and your marketplace cart.' ) }}</span>
                </span>
            </a>
        </div>
    </div>
</template>

<script lang="ts">
import { nsConfirmPopup } from '~/components/components';
import { Popup } from '~/libraries/popup';
import popupCloser from '~/libraries/popup-closer';

declare const __;
declare const authorizationUrl;
declare const nsHttpClient;
declare const nsSnackBar;

export default {
    name: 'ns-marketplace-settings',
    props: [ 'popup' ],
    data() {
        return {
            authorizationUrl,
            isDisconnecting: false,
            isRefreshing: false,
        };
    },
    computed: {
        isConnected() {
            return this.popup.params.isConnected === true;
        },
    },
    mounted() {
        this.popupCloser();
    },
    methods: {
        __,
        popupCloser,
        close() {
            this.popup.close();
        },
        refreshCache() {
            this.isRefreshing = true;

            nsHttpClient.delete( '/api/marketplace/cache' ).subscribe( {
                next: response => {
                    nsSnackBar.success( response.message || __( 'The marketplace cache has been cleared.' ) );
                    window.location.reload();
                },
                error: error => {
                    this.isRefreshing = false;
                    nsSnackBar.error( error.message || __( 'Failed to clear the marketplace cache.' ) );
                },
            } );
        },
        disconnect() {
            Popup.show( nsConfirmPopup, {
                title: __( 'Confirm your action' ),
                message: __( 'Disconnect this NexoPOS installation from My NexoPOS? You will need to connect again before accessing licenses, downloads, or your marketplace cart.' ),
                onAction: ( action ) => {
                    if ( action ) {
                        this.isDisconnecting = true;

                        nsHttpClient.delete( '/api/marketplace/connection' ).subscribe( {
                            next: response => {
                                nsSnackBar.success( response.message || __( 'This installation has been disconnected from My NexoPOS.' ) );
                                window.location.reload();
                                this.isDisconnecting = false;
                            },
                            error: error => {
                                this.isDisconnecting = false;
                                nsSnackBar.error( error.message || __( 'Failed to disconnect this installation from My NexoPOS.' ) );
                            },
                        } );
                    }
                }
            });
        },
    },
};
</script>
