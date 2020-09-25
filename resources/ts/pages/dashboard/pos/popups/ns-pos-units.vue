<template>
    <div class="h-full w-full">
        <div id="header" class="h-16 flex justify-center items-center">
            <h3 class="font-bold text-gray-700">Choose Selling Unit</h3>
        </div>
        <div class="grid grid-flow-row grid-cols-4 grid-rows-4">
            <div @click="selectUnit( unit )" :key="unit.id" v-for="unit of units" :class="unit.selected ? 'bg-blue-100' : ''" class="hover:bg-blue-100 h-56 flex items-center justify-center flex-col cursor-pointer border border-gray-200">
                <img :src="unit.preview_url" alt="" class="w-32 h-32">
                <h4 class="font-semibold text-xl my-2 text-gray-700 text-center">{{ unit.name }}</h4>
            </div>
        </div>
    </div>
</template>
<script>
import { nsHttpClient } from '../../../../bootstrap';
export default {
    data() {
        return {
            units: []
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

        this.loadUnits();
    },
    methods: {
        loadUnits() {
            nsHttpClient.get( `/api/nexopos/v4/units/pos?ids=${this.$popupParams.product.selling_unit_ids}` )
                .subscribe( result => {
                    this.units  =   result;
                })
        },
        selectUnit( unit ) {
            // this.types.forEach( type => type.selected = false );
            // type.selected   =   true;
            // POS.order.types.next( this.types );
            // this.$popup.close();
        }
    }
}
</script>