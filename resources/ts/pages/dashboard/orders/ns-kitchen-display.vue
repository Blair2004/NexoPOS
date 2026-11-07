<template>
    <div id="ns-kitchen-display">
        <div class="flex flex-wrap items-center gap-2 mb-4">
            <button
                v-for="zone in [{ id: null, name: __( 'All Zones' ) }, ...zones]"
                :key="zone.id ?? 'all'"
                @click="selectZone( zone.id )"
                :class="selectedZone === zone.id ? 'bg-blue-600 text-white' : 'bg-white text-gray-700 border border-gray-300'"
                class="px-3 py-2 rounded cursor-pointer font-semibold text-sm">
                {{ zone.name }}
                <span v-if="zone.pending_items" class="ml-1 px-1 rounded bg-red-500 text-white text-xs">{{ zone.pending_items }}</span>
            </button>
            <button @click="load( false )" class="ml-auto px-3 py-2 rounded cursor-pointer font-semibold text-sm bg-white text-gray-700 border border-gray-300">
                {{ __( 'Refresh' ) }}
            </button>
        </div>

        <p v-if="config.auto_print" class="text-sm text-gray-500 mb-4">{{ __( 'Auto print is enabled: the print dialog opens automatically for each new order on this screen.' ) }}</p>

        <div v-if="loading && orders.length === 0" class="text-center py-8 text-gray-500">{{ __( 'Loading...' ) }}</div>
        <div v-else-if="orders.length === 0" class="text-center py-8">
            <i class="las la-utensils text-5xl text-gray-400"></i>
            <p class="text-gray-500 mt-2">{{ __( 'No open orders' ) }}</p>
        </div>

        <div v-else class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
            <div v-for="order in orders" :key="order.id" :class="order.is_late ? 'border-red-500' : 'border-gray-200'" class="bg-white rounded shadow border-2 flex flex-col">
                <div class="p-3 border-b flex items-start justify-between gap-2">
                    <div>
                        <h3 class="text-xl font-bold">#{{ order.code }}</h3>
                        <p class="text-sm text-gray-500">{{ order.type_label }}<span v-if="order.customer_name"> · {{ order.customer_name }}</span></p>
                        <p v-if="order.note" class="text-sm text-amber-700 mt-1">{{ __( 'Note' ) }}: {{ order.note }}</p>
                    </div>
                    <div class="text-right shrink-0">
                        <span :class="order.is_late ? 'bg-red-600 text-white' : 'bg-gray-100 text-gray-700'" class="px-2 py-1 rounded text-sm font-bold">
                            {{ order.waiting_minutes }} {{ __( 'Minutes' ) }}
                        </span>
                        <p v-if="order.is_late" class="text-xs font-bold text-red-600 mt-1">{{ __( 'Late' ) }}</p>
                    </div>
                </div>

                <ul class="flex-auto divide-y">
                    <li v-for="item in order.items" :key="item.id" class="p-3 flex items-center gap-2">
                        <span class="font-bold text-lg w-12 shrink-0">{{ item.quantity }}{{ item.unit_name ? ' ' + item.unit_name : '' }}</span>
                        <span class="flex-auto" :class="item.kitchen_status === 'done' ? 'line-through text-gray-400' : ''">
                            {{ item.name }}
                            <span class="block text-xs text-gray-400">{{ item.category_name }}</span>
                        </span>
                        <span :class="statusClass( item.kitchen_status )" class="px-2 py-1 rounded text-xs font-bold shrink-0">{{ statusLabel( item.kitchen_status ) }}</span>
                        <span class="flex gap-1 shrink-0">
                            <button v-if="item.kitchen_status === 'pending'" @click="markItem( order, item, 'preparing' )" :disabled="updating[item.id]" class="px-2 py-1 rounded cursor-pointer text-xs font-semibold bg-amber-500 text-white disabled:opacity-50">{{ __( 'Start' ) }}</button>
                            <button v-if="item.kitchen_status !== 'done'" @click="markItem( order, item, 'done' )" :disabled="updating[item.id]" class="px-2 py-1 rounded cursor-pointer text-xs font-semibold bg-green-600 text-white disabled:opacity-50">{{ __( 'Done' ) }}</button>
                            <button v-if="item.kitchen_status === 'done'" @click="markItem( order, item, 'pending' )" :disabled="updating[item.id]" class="px-2 py-1 rounded cursor-pointer text-xs font-semibold bg-gray-200 text-gray-700 disabled:opacity-50">{{ __( 'Redo' ) }}</button>
                        </span>
                    </li>
                </ul>

                <div class="p-3 border-t flex items-center justify-between">
                    <span class="text-sm text-gray-500">{{ doneCount( order ) }}/{{ order.items.length }} {{ __( 'Done' ) }}</span>
                    <button @click="printTicket( order )" class="px-3 py-2 rounded cursor-pointer text-sm font-semibold bg-blue-600 text-white">
                        {{ __( 'Print Kitchen Ticket' ) }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
<script lang="ts">
import { __ } from '~/libraries/lang';
import { nsHttpClient, nsSnackBar } from '~/bootstrap';

export default {
    name: 'ns-kitchen-display',
    props: [ 'config' ],
    data() {
        return {
            zones: [],
            orders: [],
            selectedZone: null,
            loading: false,
            timer: null,
            printedOrderIds: [],
            seenOrderIds: [],
            updating: {},
        }
    },
    mounted() {
        try {
            this.printedOrderIds = JSON.parse( localStorage.getItem( 'ns-kds-printed-orders' ) || '[]' );
        } catch ( e ) {
            this.printedOrderIds = [];
        }

        this.load( true );
        this.timer = setInterval( () => {
            if ( ! document.hidden ) {
                this.load( false );
            }
        }, ( this.config.refresh_interval || 15 ) * 1000 );
    },
    beforeUnmount() {
        if ( this.timer ) {
            clearInterval( this.timer );
        }
    },
    methods: {
        __,
        selectZone( zoneId ) {
            this.selectedZone = zoneId;
            this.load( true );
        },
        load( initial ) {
            this.loading = true;
            const url = this.config.orders_url + ( this.selectedZone ? `?zone=${this.selectedZone}` : '' );

            nsHttpClient.get( url ).subscribe({
                next: result => {
                    this.zones = result.zones || [];
                    this.orders = result.orders || [];

                    const currentIds = this.orders.map( order => order.id );

                    if ( initial ) {
                        // Orders already on screen when the KDS opens are not
                        // auto-printed: only orders that arrive afterwards are.
                        this.seenOrderIds = currentIds;
                    } else if ( this.config.auto_print ) {
                        const freshIds = currentIds.filter( id => ! this.seenOrderIds.includes( id ) && ! this.printedOrderIds.includes( id ) );

                        freshIds.forEach( id => {
                            const order = this.orders.find( order => order.id === id );

                            if ( order ) {
                                this.printTicket( order );
                                this.printedOrderIds.push( id );
                            }
                        } );

                        this.printedOrderIds = this.printedOrderIds.slice( -200 );
                        localStorage.setItem( 'ns-kds-printed-orders', JSON.stringify( this.printedOrderIds ) );
                        this.seenOrderIds = currentIds;
                    } else {
                        this.seenOrderIds = currentIds;
                    }

                    this.loading = false;
                },
                error: error => {
                    this.loading = false;
                    nsSnackBar.error( error.message || __( 'Unexpected error occurred.' ) );
                }
            } );
        },
        markItem( order, item, status ) {
            this.updating = { ...( this.updating || {} ), [ item.id ]: true };

            nsHttpClient.post( this.config.item_status_url.replace( '{id}', item.id ), {
                kitchen_status: status,
            } ).subscribe({
                next: result => {
                    item.kitchen_status = status;
                    this.updating = { ...this.updating, [ item.id ]: false };

                    if ( result.data?.order?.all_items_done ) {
                        nsSnackBar.success( __( 'Order is ready' ) );
                        this.load( false );
                    }
                },
                error: error => {
                    this.updating = { ...this.updating, [ item.id ]: false };
                    nsSnackBar.error( error.message || __( 'Unexpected error occurred.' ) );
                }
            } );
        },
        printTicket( order ) {
            const zoneSegment = this.selectedZone ? `/${this.selectedZone}` : '';
            const url = this.config.ticket_url
                .replace( '{order}', order.id )
                .replace( '/{zone}', zoneSegment );

            window.open( url, '_blank' );
        },
        doneCount( order ) {
            return order.items.filter( item => item.kitchen_status === 'done' ).length;
        },
        statusLabel( status ) {
            return {
                pending: __( 'Pending' ),
                preparing: __( 'Preparing' ),
                done: __( 'Done' ),
            }[ status ] || status;
        },
        statusClass( status ) {
            return {
                pending: 'bg-gray-100 text-gray-700',
                preparing: 'bg-amber-100 text-amber-800',
                done: 'bg-green-100 text-green-800',
            }[ status ] || 'bg-gray-100 text-gray-700';
        },
    }
}
</script>
