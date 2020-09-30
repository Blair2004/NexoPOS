<template>
    <div id="discount-popu" class="bg-white shadow min-h-2/5-screen w-screen md:w-3/5-screen lg:w-2/5-screen xl:w-1/5-screen relative">
        <div class="flex-shrink-0 py-2 border-b border-gray-200">
            <h1 class="text-xl font-bold text-gray-700 text-center" v-if="type === 'product'">Product Discount</h1>
            <h1 class="text-xl font-bold text-gray-700 text-center" v-if="type === 'cart'">Cart Discount</h1>
        </div>
        <div id="screen" class="h-16 bg-blue-600 text-white border-gray-200 flex items-center justify-center">
            <h1 class="font-bold text-3xl">
                <span v-if="mode === 'flat'">{{ finalValue  | currency }}</span>
                <span v-if="mode === 'percentage'">{{ finalValue }}%</span>
            </h1>
        </div>
        <div id="switch-mode" class="flex">
            <button @click="setPercentageType('flat')" :class="mode === 'flat' ? 'bg-blue-600 text-white' : ''" class="outline-none w-1/2 py-2 flex items-center justify-center">Flat</button>
            <hr class="border-r border-gray-200">
            <button @click="setPercentageType('percentage')" :class="mode === 'percentage' ? 'bg-blue-600 text-white' : ''" class="outline-none w-1/2 py-2 flex items-center justify-center">Percentage</button>
        </div>
        <div id="numpad" class="grid grid-flow-row grid-cols-3 grid-rows-3">
            <div 
                @click="inputValue( key )"
                :key="index" 
                v-for="(key,index) of keys" 
                class="hover:bg-blue-400 hover:text-white hover:border-blue-600 text-xl font-bold border border-gray-200 h-24 flex items-center justify-center cursor-pointer">
                <span v-if="key.value !== undefined">{{ key.value }}</span>
                <i v-if="key.icon" class="las" :class="key.icon"></i>
            </div>
        </div>
    </div>
</template>
<script>
export default {
    name: 'ns-pos-discount-popup',
    data() {
        return {
            finalValue: 1,
            virtualStock: null,
            popupSubscription: null,
            mode: '',
            type: '',
            allSelected: true,
            isLoading: false,
            keys: [
                ...([1,2,3].map( key => ({ identifier: key, value: key }))),
                ...([4,5,6].map( key => ({ identifier: key, value: key }))),
                ...([7,8,9].map( key => ({ identifier: key, value: key }))),
                ...[{ identifier: 'backspace', icon : 'la-backspace' },{ identifier: 0, value: 0 }, { identifier: 'next', icon: 'la-share' }],
            ]
        }
    },
    mounted() {
        this.mode           =   this.$popupParams.reference.discount_type || 'percentage';
        this.type           =   this.$popupParams.type;

        if ( this.mode === 'percentage' ) {
            this.finalValue     =   this.$popupParams.reference.discount_percentage || 1;
        } else {
            this.finalValue     =   this.$popupParams.reference.discount_amount || 1;
        }

        this.$popup.event.subscribe( (action ) =>  {
            if ( action.event === 'click-overlay' ) {
                this.$popup.close();
            }
        })
    },
    methods: {
        setPercentageType( mode ) {
            this.mode       =   mode;
        },
        inputValue( key ) {
            if ( key.identifier === 'next' ) {
                this.$popupParams.onSubmit({
                    discount_type           :   this.mode,
                    discount_percentage     :   this.mode === 'percentage' ? this.finalValue : undefined,
                    discount_amount         :   this.mode === 'flat' ? this.finalValue : undefined
                });
                this.$popup.close();
            } else if ( key.identifier === 'backspace' ) {
                if ( this.allSelected ) {
                    this.finalValue     =   0;
                    this.allSelected    =   false;
                } else {
                    this.finalValue     =   this.finalValue.toString();
                    this.finalValue     =   this.finalValue.substr(0, this.finalValue.length - 1 ) || 0;
                }
            } else {
                if ( this.allSelected ) {
                    this.finalValue     =   key.value;
                    this.finalValue     =   parseFloat( this.finalValue );
                    this.allSelected    =   false;
                } else {
                    this.finalValue     +=  '' + key.value;
                    this.finalValue     =   parseFloat( this.finalValue );

                    if ( this.mode === 'percentage' ) {
                        this.finalValue = this.finalValue > 100 ? 100 : this.finalValue;
                    }
                }
            } 
        }
    }
}
</script>