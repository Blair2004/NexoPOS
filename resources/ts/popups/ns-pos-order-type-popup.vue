<template>
    <div class="h-full w-4/5-screen md:w-2/5-screen lg:w-2/5-screen xl:w-2/6-screen bg-white shadow-lg">
        <div id="header" class="h-16 flex justify-center items-center">
            <h3 class="font-bold text-gray-700">{{ __( 'Define The Order Type' ) }}</h3>
        </div>
        <div class="grid grid-flow-row grid-cols-1 grid-rows-1" v-if="types.length === 0">
            <div class="h-full w-full flex items-center justify-center flex-col">
                <i class="las la-frown text-6xl"></i>
                <p class="text-center text-sm text-gray-600 p-4 w-2/3">{{ __( 'No payment type has been selected on the settings. Please check your Settings > POS > Features and choose the supported order type.' ) }}</p>
            </div>
        </div>
        <div class="grid grid-flow-row grid-cols-2 grid-rows-2" v-if="types.length > 0">
            <div @click="select( type.identifier )" :key="type.identifier" v-for="type of types" :class="type.selected ? 'bg-blue-100' : ''" class="hover:bg-blue-100 h-56 flex items-center justify-center flex-col cursor-pointer border border-gray-200">
                <img :src="type.icon" alt="" class="w-32 h-32">
                <h4 class="font-semibold text-xl my-2 text-gray-700">{{ type.label }}</h4>
            </div>
        </div>
    </div>
</template>
<script>
import resolveIfQueued from '@/libraries/popup-resolver';
import nsPosShippingPopupVue from './ns-pos-shipping-popup.vue';
import { __ } from '@/libraries/lang';
export default {
    data() {
        return {
            types: [],
            typeSubscription: null,
        }
    },
    mounted() {
        this.$popup.event.subscribe( action => {
            if ( action.event === 'click-overlay' ) {
                this.resolveIfQueued( false );
            }
        });
        
        this.typeSubscription   =   POS.types.subscribe( types => {
            this.types  =   types;
        });
    },
    destroyed() {
        this.typeSubscription.unsubscribe();
    },
    methods: {
        __,
        
        resolveIfQueued,

        async select( type ) {
            Object.values( this.types )
                .forEach( _type => _type.selected = false );
            
            this.types[ type ].selected     =   true;
            const selectedType              =   this.types[ type ];

            POS.types.next( this.types );

            /**
             * treat all the promises
             * that are registered within 
             * the orderType queue
             */
            await POS.triggerOrderTypeSelection( selectedType );

            this.resolveIfQueued( selectedType );
        }
    }
}
</script>