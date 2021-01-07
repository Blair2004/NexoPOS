<script>
import { nsHttpClient } from '../bootstrap';
export default {
    data() {
        return {
            registers: [],
            selectedRegister: null,
            hasLoadedRegisters: false,
            fields: []
        }
    },
    mounted() {
        this.loadRegisters()
    },
    methods: {
        selectRegister( register ) {
            this.selectedRegister   =   register;
            this.loadFields();
        },
        returnBack() {
            this.selectedRegister   =   null;
            this.loadRegisters();
        },
        loadRegisters() {
            this.hasLoadedRegisters     =   false;
            nsHttpClient.get( `/api/nexopos/v4/cash-registers` )
                .subscribe( result => {
                    this.registers              =   result;
                    this.hasLoadedRegisters     =   true;
                })
        },
        loadFields() {
            nsHttpClient.get( `/api/nexopos/v4/fields/ns.cash-registers-opening` )
                .subscribe( result => {

                })
        }
    }
}
</script>
<template>
    <div class="w-95vw h-95vh md:w-2/5-screen md:h-3/5-screen shadow-lg flex flex-col bg-white overflow-hidden">
        <div class="title p-2 border-b border-gray-200 flex justify-between items-center">
            <h3 class="font-semibold">Open The Register</h3>
            <div>
                <button v-if="selectedRegister !== null" @click="returnBack()" class="text-sm rounded-lg border border-gray-400 px-3 py-1 hover:bg-red-500 hover:border-red-500 hover:text-white">
                    <i class="las la-arrow-left"></i>
                    <span class="pl-2">Return</span>
                </button>
            </div>
        </div>
        <div class="p-2 flex-auto overflow-y-auto flex items-center justify-center" v-if="selectedRegister === null && ! hasLoadedRegisters">
            <ns-spinner size="16" border="4"></ns-spinner>
        </div>
        <div class="flex-auto overflow-y-auto" v-if="selectedRegister === null && hasLoadedRegisters">
            <div class="grid grid-cols-3">
                <div @click="selectRegister( register )" v-for="(register, index) of registers" :key="index" class="hover:bg-blue-400 hover:text-white cursor-pointer border-b border-r border-gray-200 flex items-center justify-center flex-col p-3 text-gra">
                    <i class="las la-cash-register text-6xl"></i>
                    <h3 class="text-semibold text-center">{{ register.name }}</h3>
                    <span class="text-sm">({{ register.status }})</span>
                </div>
            </div>
        </div>
        <div class="p-2 flex-auto overflow-y-auto" v-if="selectedRegister !== null">

        </div>
    </div>
</template>