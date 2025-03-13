<template>
    <div id="notificaton-wrapper">
        <div id="notification-button" @click="visible = !visible" :class="visible ? 'panel-visible border-0 shadow-lg' : 'border panel-hidden'" class="hover:shadow-lg hover:border-opacity-0 rounded-full h-12 w-12 cursor-pointer font-bold text-2xl justify-center items-center flex">
            <div class="relative float-right" v-if="notifications.length > 0">
                <div class="absolute -ml-6 -mt-8">
                    <div class="counter w-8 h-8 rounded-full text-xs flex items-center justify-center">{{ nsNumberAbbreviate( notifications.length, 'abbreviate' ) }}</div>
                </div>
            </div>
            <i class="las la-bell"></i>
        </div>
        <div class="h-0 w-0" v-if="visible" id="notification-center">
            <div class="absolute left-0 top-0 sm:relative w-screen zoom-out-entrance anim-duration-300 h-[80vh] sm:w-64 sm:h-108 flex flex-row-reverse">
                <div class="z-50 sm:rounded-lg shadow-lg h-full w-full md:mt-2 overflow-y-hidden flex flex-col">
                    <div @click="visible = false" class="sm:hidden p-2 cursor-pointer flex items-center justify-center border-b border-gray-200">
                        <h3 class="font-semibold hover:text-info-primary">Close</h3>
                    </div>
                    <div class="overflow-y-auto flex flex-col flex-auto">
                        <div :key="notification.id" v-for="notification of notifications" class="notification-card notice border-b">
                            <div class="p-2" :class="notification.actions === null ? 'cursor-pointer' : ''" @click="triggerLink( notification )">
                                <div class="flex items-center justify-between">
                                    <h1 class="font-semibold">{{ notification.title }}</h1>
                                    <ns-close-button @click="closeNotice( $event, notification )"></ns-close-button>
                                </div>
                                <p class="py-1 text-sm">{{ notification.description }}</p>
                                <div class="flex justify-between">
                                    <div class="-mx-1 flex">
                                        <div class="ns-button info px-1" v-for="action of notification.actions">
                                            <button @click="executeAsync( action, notification )" class="text-xs cursor-pointer px-2 py-1">{{ action.label }}</button>
                                        </div>
                                    </div>
                                    <div>
                                        <span class="text-xs date">{{ timespan( notification.updated_at ) }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div v-if="notifications.length === 0" class="h-full w-full flex items-center justify-center">
                            <div class="flex flex-col items-center">
                                <i class="las la-laugh-wink text-5xl text-font"></i>
                                <p class="text-fontcolor-soft text-sm">{{ __( 'Nothing to care about !' ) }}</p>
                            </div>
                        </div>
                    </div>
                    <div class="cursor-pointer clear-all">
                        <h3 @click="deleteAll()" class="text-sm p-2 flex items-center justify-center w-full font-semibold ">{{ __( 'Clear All' ) }}</h3>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
<script lang="ts">
import { nsHttpClient, nsSnackBar } from '~/bootstrap';
import { __ } from '~/libraries/lang';
import nsPosConfirmPopupVue from '~/popups/ns-pos-confirm-popup.vue';
import nsCloseButton from '~/components/ns-close-button.vue';
import { nsNumberAbbreviate } from '~/filters/currency';
import { timespan } from '~/libraries/timespan';
import NsButton from '~/components/ns-button.vue';
import { nsConfirmPopup } from '~/components/components';
import { StatusResponse } from '~/status-response';

declare const Echo;
declare const ns;
declare const Popup;

export default {
    name: 'ns-notifications',
    data() {
        return {
            notifications: [],
            visible: false,
            socketEnabled: false,
            interval: null,
        }
    },
    components: {
        nsCloseButton,
        NsButton,
    },
    mounted() {
        document.addEventListener( 'click', this.checkClickedItem );

        /**
         * if Reverb is connected, there is no need to
         * continusly check for notifications
         */
        if ( typeof Echo === 'undefined' ) {
            this.interval   =   setInterval( () => {
                this.loadNotifications();
            }, 15000 );
        } else {
            this.interval   =   setInterval( () => {
                this.socketEnabled  =   Echo.connector.pusher.connection.state === 'connected';
            }, 1000 );

            Echo.private( `App.User.${ns.user.attributes.user_id}` )
                .listen( 'NotificationUpdatedEvent', ( NotificationUpdatedEvent ) => {
                    this.pushNotificationIfNew( NotificationUpdatedEvent.notification );
                })
                .listen( 'NotificationCreatedEvent', ( NotificationCreatedEvent ) => {
                    this.pushNotificationIfNew( NotificationCreatedEvent.notification );
                })
                .listen( 'NotificationDeletedEvent', ( NotificationDeletedEvent ) => {
                    this.deleteNotificationIfExists( NotificationDeletedEvent.notification );
                });
        }

        this.loadNotifications();
    },
    unmounted() {
        clearInterval( this.interval );
    },
    methods: {
        __,
        timespan,
        nsNumberAbbreviate,
        executeAsync( action, notification ) {
            Popup.show( nsConfirmPopup, {
                title: __( 'Confirm Your Action' ),
                message: action.message || __( 'Are you sure you want to perform this action ?' ),
                onAction: ( hasConfirmed ) => {
                    if ( hasConfirmed ) {
                        const verb =   action.data !== undefined ? 'get' : 'post';

                        /**
                         * We'll keep the reference of the notification
                         * so that after the Async operation, the notification
                         * are pulled from the server.
                         */
                        action.url.search( '/\?/' ) === -1 ? action.url += `?notification_id=${notification.id}` : action.url += `&notification_id=${notification.id}`;

                        nsHttpClient[ verb ]( action.url, action.data )
                            .subscribe({
                                next: (result: StatusResponse ) => {
                                    nsSnackBar.success( result.message );
                                    this.loadNotifications();
                                },
                                error: (error) => {
                                    nsSnackBar.error( error.message );
                                }
                            })
                    }
                }
            })
        },
        pushNotificationIfNew( notification ) {
            const exists     =   this.notifications.filter( _notification => _notification.id === notification.id ).length > 0;

            if ( ! exists ) {
                this.notifications.unshift( notification );
            }
        },
        deleteNotificationIfExists( notification ) {
            const exists     =   this.notifications.filter( _notification => _notification.id === notification.id );

            if ( exists.length > 0 ) {
                const index     =   this.notifications.indexOf( exists[0] );
                this.notifications.splice( index, 1 );
            }
        },
        deleteAll() {
            Popup.show( nsPosConfirmPopupVue, {
                title: __( 'Confirm Your Action' ),
                message: __( 'Would you like to clear all the notifications ?' ),
                onAction: ( action ) => {
                    if ( action ) {
                        nsHttpClient.delete( `/api/notifications/all` )
                            .subscribe( ( result: StatusResponse ) => {
                                nsSnackBar.success( result.message );
                            })
                    }
                }
            })
        },
        checkClickedItem( event ) {
            let clickChildrens;

            if ( document.getElementById( 'notification-center' ) ) {
                clickChildrens        =   document.getElementById( 'notification-center' ).contains( event.srcElement );
            } else {
                clickChildrens        =   false;
            }
            
            const isNotificationButton  =   document.getElementById( 'notification-button' ).contains( event.srcElement );

            if ( ! clickChildrens && ! isNotificationButton && this.visible ) {
                this.visible    =   false;
            }
        },

        loadNotifications() {
            nsHttpClient.get( '/api/notifications' )
                .subscribe( notifications => {
                    this.notifications  =   notifications;
                })
        },

        triggerLink( notification ) {
            if ( notification.url !== 'url' && notification.actions === null ) {
                return window.open( notification.url, '_blank' );
            }
        },

        closeNotice( event, notification ) {
            nsHttpClient.delete( `/api/notifications/${notification.id}` )
                .subscribe( result => {
                    if ( ! this.socketEnabled ) {
                        this.loadNotifications();
                    }
                });
            event.stopPropagation();
        }
    }
}
</script>