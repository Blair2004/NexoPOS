<template>
    <div class="h-full w-full overflow-hidden flex flex-col">
        <div id="header" class="h-16 flex justify-center items-center flex-shrink-0">
            <h3 class="font-bold text-gray-700">Choose Selling Unit</h3>
        </div>
        <div class="grid grid-flow-row grid-cols-3 overflow-y-auto">
            <div @click="selectUnit( unit )" :key="unit.id" v-for="unit of units" class="hover:bg-gray-200 cursor-pointer border flex-shrink-0 border-gray-200 flex flex-col items-center justify-center">
                <div class="h-full w-full p-2 flex items-center justify-center overflow-hidden">
                    <img v-if="unit.preview_url" :src="unit.preview_url" class="object-center h-40" :alt="unit.name">
                    <i class="las la-image text-gray-600 text-6xl" v-if="! unit.preview_url"></i>
                </div>
                <div class="h-0 w-full">
                    <div class="relative w-full flex items-center justify-center -top-10 h-20 py-2" style="background:rgb(255 255 255 / 73%)">
                        <h3 class="text-sm font-bold text-gray-700 py-2 text-center">{{ unit.name }}</h3>
                    </div>
                </div>
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
        /**
         * we'll resolve a value that
         * will be added to the object
         * built at the end
         * @param Unit
         */
        selectUnit( unit ) {
            this.$popupParams.resolve({
                unit_id     :   unit.id
            });
            this.$popup.close();
            // this.types.forEach( type => type.selected = false );
            // type.selected   =   true;
            // POS.order.types.next( this.types );
        }
    }
}
</script>