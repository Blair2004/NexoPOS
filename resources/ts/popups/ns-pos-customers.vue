<template>
    <div class="bg-white shadow-lg rounded w-4/5-screen h-4/5-screen lg:w-3/5-screen flex flex-col overflow-hidden">
        <div class="p-2 flex justify-between items-center border-b border-gray-400">
            <h3 class="font-semibold text-gray-700">Customers</h3>
            <div>
                <ns-close-button @click="$popup.close()"></ns-close-button>
            </div>
        </div>
        <div class="flex-auto flex p-2 bg-gray-200 overflow-y-auto">
            <ns-tabs :active="activeTab" @active="activeTab = $event">
                <ns-tabs-item identifier="create-customers" label="New Customer">
                    <ns-crud-form 
                        @save="handleSavedCustomer( $event )"
                        submit-url="/api/nexopos/v4/crud/ns.customers"
                        src="/api/nexopos/v4/crud/ns.customers/form-config">
                        <template v-slot:title>Customer Name</template>
                        <template v-slot:save>Save Customer</template>
                    </ns-crud-form>
                </ns-tabs-item>
                <ns-tabs-item identifier="customer-account" label="Customer Account">
                    <div class="flex-auto w-full flex items-center justify-center flex-col" v-if="customer === null">
                        <i class="lar la-frown text-6xl text-gray-700"></i>
                        <h3 class="font-medium text-2xl text-gray-700">No Customer Selected</h3>
                        <p class="text-gray-600">In order to see a customer account, you need to select one customer.</p>
                        <div class="my-2">
                            <ns-button @click="openCustomerSelection()" type="info">Select Customer</ns-button>
                        </div>
                    </div>
                    <div v-if="customer">
                        <div class="-mx-4 flex flex-wrap">
                            <div class="px-4 mb-4 w-full">
                                <h2 class="font-semibold text-gray-700">Summary For : {{ customer.name }}</h2>
                            </div>
                            <div class="px-4 mb-4 w-full md:w-1/3">
                                <div class="rounded-lg shadow bg-transparent bg-gradient-to-br from-green-400 to-green-700 p-2 flex flex-col text-white">
                                    <h3 class="font-medium text-xl">Total Purchases</h3>
                                    <div class="w-full flex justify-end">
                                        <h2 class="text-3xl font-bold">{{ customer.purchases_amount | currency }}</h2>
                                    </div>
                                </div>
                            </div>
                            <div class="px-4 mb-4 w-full md:w-1/3">
                                <div class="rounded-lg shadow bg-transparent bg-gradient-to-br from-red-500 to-red-700 p-2 text-white">
                                    <h3 class="font-medium text-xl">Total Owed</h3>
                                    <div class="w-full flex justify-end">
                                        <h2 class="text-3xl font-bold">{{ customer.owed_amount | currency }}</h2>
                                    </div>
                                </div>
                            </div>
                            <div class="px-4 mb-4 w-full md:w-1/3">
                                <div class="rounded-lg shadow bg-transparent bg-gradient-to-br from-blue-500 to-blue-700 p-2 text-white">
                                    <h3 class="font-medium text-xl">Account Amount</h3>
                                    <div class="w-full flex justify-end">
                                        <h2 class="text-3xl font-bold">{{ customer.account_amount | currency }}</h2>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </ns-tabs-item>
            </ns-tabs>
        </div>
    </div>
</template>
<script>
import closeWithOverlayClicked from "@/libraries/popup-closer";
import { nsSnackBar } from '@/bootstrap';
import { Popup } from '@/libraries/popup';
import nsPosCustomerSelectPopupVue from './ns-pos-customer-select-popup.vue';
export default {
    name: 'ns-pos-customers',
    data() {
        return {
            activeTab: 'create-customers',
            customer: null,
        }
    },  
    mounted() {
        this.closeWithOverlayClicked();

        if ( this.$popupParams.customer !== undefined ) {
            this.activeTab  =   'customer-account';
            this.customer   =   this.$popupParams.customer;
        }
    },
    methods: {
        closeWithOverlayClicked,

        openCustomerSelection() {
            this.$popup.close();
            Popup.show( nsPosCustomerSelectPopupVue );
        },

        handleSavedCustomer( response ) {
            nsSnackBar.success( response.message ).subscribe();
            POS.definedCustomer( response.entry );
            this.$popup.close();
        }
    }
}
</script>