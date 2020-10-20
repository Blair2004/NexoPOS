<template>
    <div class="shadow-lg bg-white w-6/7-screen h-6/7-screen flex flex-col overflow-hidden">
        <div class="p-2 flex justify-between text-gray-700 items-center border-b">
            <h3 class="font-semibold">Orders</h3>
            <div>
                <ns-close-button @click="$popup.close()"></ns-close-button>
            </div>
        </div>
        <div class="flex-auto p-2 overflow-y-auto">
            <ns-tabs :active="active" @changeTab="setActiveTab( $event )">
                <ns-tabs-item identifier="hold" label="On Hold" padding="p-0">
                    <div class="p-1">
                        <div class="flex rounded border-2 border-blue-400">
                            <input type="text" class="p-2 outline-none flex-auto">
                            <button class="w-24 bg-blue-400 text-white">Search</button>
                        </div>
                    </div>
                    <ns-crud 
                        mode="light"
                        src="/api/nexopos/v4/crud/ns.hold-orders" 
                        id="crud-table-body">
                        <template v-slot:bulk-label>Bulk Actions</template>
                    </ns-crud>
                </ns-tabs-item>
                <ns-tabs-item identifier="unpaid" label="Unpaid">

                </ns-tabs-item>
                <ns-tabs-item identifier="partially-paid" label="Partially Paid">

                </ns-tabs-item>
            </ns-tabs>
        </div>
        <div class="p-2 flex justify-between border-t bg-gray-200">
            <div></div>
            <div>
                <ns-button>close</ns-button>
            </div>
        </div>
    </div>
</template>
<script>
import { nsEvent } from '@/bootstrap';
import nsPosConfirmPopupVue from './ns-pos-confirm-popup.vue';
export default {
    methods: {
        setActiveTab( event ) {
            this.active     =   event;
        },
        openOrder( order ) {
            console.log( order );
            this.$popup.close();
        }
    },
    data() {
        return {
            active: 'hold'
        }
    },
    mounted() {
        this.$popup.event.subscribe( action => {
            if ( action.event === 'click-overlay' ) {
                this.$popup.close();
            }
        });

        nsEvent.subject().subscribe( event => {
            if ( event.identifier === 'ns-table-row-action' ) {
                const products  =   POS.products.getValue();
                if ( products.length > 0 ) {
                    return Popup.show( nsPosConfirmPopupVue, {
                        title: 'Confirm Your Action',
                        message: 'The cart is not empty. Opening an order will clear your cart would you proceed ?',
                        onAction: ( action ) => {
                            if ( action ) {
                                this.openOrder( event.value.row );
                            }
                        }
                    })
                }
                this.openOrder( event.value.row );
            }
        });
    }
}
</script>