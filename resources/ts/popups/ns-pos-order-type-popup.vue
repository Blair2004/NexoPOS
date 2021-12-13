<template>
    <div class="h-full w-4/5-screen md:w-2/5-screen lg:w-2/5-screen xl:w-2/6-screen bg-white shadow-lg">
        <div id="header" class="h-16 flex justify-center items-center">
            <h3 class="font-bold text-gray-700">{{ __( 'Define The Order Type' ) }}</h3>
        </div>
        <div class="grid grid-flow-row grid-cols-1 grid-rows-1" v-if="Object.values( types ).length === 0">
            <div class="h-full w-full flex items-center justify-center flex-col">
                <i class="las la-frown text-7xl text-red-400"></i>
                <div class="p-4 md:w-2/3">
                    <p class="text-center text-gray-600">{{ __( 'No payment type has been selected on the settings. Please check your POS features and choose the supported order type' ) }}</p>
                    <div class="flex justify-center py-1">
                        <ns-link target="_blank" type="info" href="https://my.nexopos.com/en/documentation/components/order-types">{{ __( 'Read More' ) }}</ns-link>
                    </div>
                </div>
            </div>
        </div>
        <div class="grid grid-flow-row grid-cols-2 grid-rows-2" v-if="Object.values( types ).length > 0">
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

            /**
             * treat all the promises
             * that are registered within 
             * the orderType queue
             */
            try {
                const result    =   await POS.triggerOrderTypeSelection( selectedType );
                POS.types.next( this.types );
                this.resolveIfQueued( selectedType );
            } catch( exception ) {
                // ...
            }
        }
    }
}
</script>