<template>
    <div class="h-full w-full">
        <div class="flex-shrink-0 py-2 border-b border-gray-200">
            <h1 class="text-xl font-bold text-gray-700 text-center">Define Quantity</h1>
        </div>
        <div id="screen" class="h-24 border-b bg-gray-800 text-white border-gray-200 flex items-center justify-center">
            <h1 class="font-bold text-3xl">{{ finalValue }}</h1>
        </div>
        <div id="numpad" class="grid grid-flow-row grid-cols-3 grid-rows-3">
            <div 
                @click="inputValue( key )"
                :key="index" 
                v-for="(key,index) of keys" 
                class="hover:bg-blue-400 hover:text-white hover:border-blue-600 text-xl font-bold border border-gray-200 h-32 flex items-center justify-center cursor-pointer">
                <span v-if="key.value !== undefined">{{ key.value }}</span>
                <i v-if="key.icon" class="las" :class="key.icon"></i>
            </div>
        </div>
    </div>
</template>
<script>
export default {
    data() {
        return {
            finalValue: 0,
            keys: [
                ...([1,2,3].map( key => ({ identifier: key, value: key }))),
                ...([4,5,6].map( key => ({ identifier: key, value: key }))),
                ...([7,8,9].map( key => ({ identifier: key, value: key }))),
                ...[{ identifier: 'backspace', icon : 'la-backspace' },{ identifier: 0, value: 0 }, { identifier: 'next', icon: 'la-share' }],
            ]
        }
    },
    mounted() {
        this.$popup.event.subscribe( action => {
            if ( action.event === 'click-overlay' ) {
                /**
                 * as this runs under a Promise
                 * we need to make sure that
                 * it resolve false using the "resolve" function
                 * provided as $popupParams.
                 * Here we resolve "false" as the user has broken the Promise
                 */
                this.$popupParams.reject( false );

                /**
                 * we can safely close the popup.
                 */
                this.$popup.close();
            }
        });
    },
    methods: {
        inputValue( key ) {
            if ( key.identifier === 'next' ) {
                /**
                 * resolve is provided only on the addProductQueue
                 */
                this.$popupParams.resolve({ quantity : this.finalValue });
                this.$popup.close();
            } else if ( key.identifier === 'backspace' ) {
                this.finalValue     =   this.finalValue.toString();
                this.finalValue     =   this.finalValue.substr(0, this.finalValue.length - 1 ) || 0;
            } else {
                this.finalValue     +=  '' + key.value;
                this.finalValue     =   parseFloat( this.finalValue );
            } 
        },
    }
}
</script>