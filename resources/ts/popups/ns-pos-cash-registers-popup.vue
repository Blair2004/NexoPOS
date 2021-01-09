<script>
import { nsHttpClient, nsSnackBar } from '../bootstrap';
import { default as nsNumpad } from "@/components/ns-numpad";
import FormValidation from '@/libraries/form-validation';
export default {
    components: {
        nsNumpad
    },
    data() {
        return {
            registers: [],
            selectedRegister: null,
            priorVerification: false,
            hasLoadedRegisters: false,
            openFields: [],
            validation: new FormValidation,
            amount: 0,
        }
    },
    mounted() {
        this.checkUsedRegister();
    },
    computed: {
    },
    
    methods: {
        selectRegister( register ) {
            if ( register.status !== 'closed' ) {
                return nsSnackBar.error( 'Unable to open this register. Only closed register can be opened.' ).subscribe();
            }

            this.selectedRegister   =   register;
            this.loadFields();
        },
        checkUsedRegister() {
            this.priorVerification  =   false;
            nsHttpClient.get( `/api/nexopos/v4/cash-registers/used` )
                .subscribe( result => {
                    this.$popupParams.resolve( result );
                    this.$popup.close();
                }, ( error ) => {
                    this.priorVerification  =   true;
                    nsSnackBar.error( error.message ).subscribe();
                    this.loadRegisters();
                });
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
                    this.openFields     =   result;
                }, ( error ) => {
                    return nsSnackBar.error( error.message, 'OKAY', { duration : false }).subscribe();
                })
        },
        definedValue( value ) {
            this.amount     =   value;
        },
        submit() {
            const fields    =   this.validation.extractFields( this.openFields );
            fields.amount   =   this.amount;

            nsHttpClient.post( `/api/nexopos/v4/cash-registers/open/${this.selectedRegister.id}`, fields )
                .subscribe( result => {
                    this.$popupParams.resolve( result );
                    this.$popup.close();
                    nsSnackBar.success( result.message ).subscribe();
                }, ( error ) => {
                    nsSnackBar.error( error.message ).subscribe();
                })
        },
        getClass( register ) {
            switch( register.status ) {
                case 'in-use':
                    return 'bg-teal-200 text-gray-800 cursor-not-allowed';
                break;
                case 'disabled':
                    return 'bg-gray-200 text-gray-700 cursor-not-allowed';
                break;
                case 'available':
                    return 'bg-green-100 text-gray-800';
                break;
            }
            return 'border-gray-200 cursor-pointer hover:bg-blue-400 hover:text-white';
        }
    }
}
</script>
<template>
    <div class="w-95vw h-95vh md:w-2/5-screen md:h-3/5-screen flex flex-col overflow-hidden" :class="priorVerification ? 'shadow-lg bg-white' : ''">
        <template v-if="priorVerification">
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
                    <div @click="selectRegister( register )" v-for="(register, index) of registers" 
                        :class="getClass( register )"
                        :key="index" class="border-b border-r flex items-center justify-center flex-col p-3">
                        <i class="las la-cash-register text-6xl"></i>
                        <h3 class="text-semibold text-center">{{ register.name }}</h3>
                        <span class="text-sm">({{ register.status }})</span>
                    </div>
                </div>
            </div>
            <div class="p-2 flex-auto overflow-y-auto" v-if="selectedRegister !== null && openFields.length > 0">
                <div class="mb-2 p-3 bg-green-400 font-bold text-white text-right">
                    {{ amount | currency }}
                </div>
                <div class="mb-2">
                    <ns-numpad @next="submit()" :value="0" @changed="definedValue( $event )"></ns-numpad>
                </div>
                <ns-field v-for="(field,index) of openFields" :field="field" :key="index"></ns-field>
            </div>
        </template>
        <div v-if="priorVerification === false" class="h-full w-full flex justify-center items-center">
            <ns-spinner size="24" border="8"></ns-spinner>
        </div>
    </div>
</template>