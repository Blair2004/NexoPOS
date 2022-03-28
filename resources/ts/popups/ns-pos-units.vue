<template>
    <div class="h-full w-full flex items-center justify-center" id="ns-units-selector">
        <div class="ns-box w-2/3-screen lg:w-1/3-screen overflow-hidden flex flex-col" v-if="unitsQuantities.length > 0">
            <div id="header" class="h-16 flex justify-center items-center flex-shrink-0">
                <h3 class="font-bold text-primary">{{ __( 'Choose Selling Unit' ) }}</h3>
            </div>
            <div v-if="unitsQuantities.length > 0" class="grid grid-flow-row grid-cols-2 overflow-y-auto">
                <div @click="selectUnit( unitQuantity )" :key="unitQuantity.id" v-for="unitQuantity of unitsQuantities" class="ns-numpad-key info cursor-pointer border flex-shrink-0 flex flex-col items-center justify-center">
                    <div class="h-40 w-full flex items-center justify-center overflow-hidden">
                        <img v-if="unitQuantity.preview_url" :src="unitQuantity.preview_url" class="object-cover h-full" :alt="unitQuantity.unit.name">
                        <div class="h-40 flex items-center justify-center" v-if="! unitQuantity.preview_url">
                            <i class="las la-image text-primary text-6xl"></i>
                        </div>
                    </div>
                    <div class="h-0 w-full">
                        <div class="relative w-full flex items-center justify-center -top-10 h-20 py-2 flex-col overlay">
                            <h3 class="font-bold text-primary py-2 text-center">{{ unitQuantity.unit.name }} ({{ unitQuantity .quantity }})</h3>
                            <p class="text-sm font-medium text-primary">{{ displayRightPrice( unitQuantity ) | currency }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="h-56 flex items-center justify-center" v-if="unitsQuantities.length === 0">
            <ns-spinner></ns-spinner>
        </div>
    </div>
</template>
<script>
import { nsHttpClient, nsSnackBar } from '@/bootstrap';
import { __ } from '@/libraries/lang';
export default {
    data() {
        return {
            unitsQuantities: [],
            loadsUnits: false,
            options: null,
            optionsSubscriber: null
        }
    },

    beforeDestroy() {
        this.optionsSubscriber.unsubscribe();
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

        this.optionsSubscriber     =   POS.options.subscribe( options => {
            this.options   =   options;
        })

        /**
         * If there is a default selected unit quantity
         * provided, we assume the product was added using the unit
         * quantity barcode.
         */
        if ( this.$popupParams.product.$original().selectedUnitQuantity !== undefined ) {
            this.selectUnit( this.$popupParams.product.$original().selectedUnitQuantity );
        } else if ( 
                this.$popupParams.product.$original().unit_quantities !== undefined && 
                this.$popupParams.product.$original().unit_quantities.length === 1 
            ) {
                this.selectUnit( this.$popupParams.product.$original().unit_quantities[0] );
        } else {
            this.loadsUnits     =   true;
            this.loadUnits();
        }
    },
    methods: {
        __,

        displayRightPrice( item ){
            return POS.getSalePrice( item, this.$popupParams.product.$original() );
        },

        loadUnits() {
            nsHttpClient.get( `/api/nexopos/v4/products/${this.$popupParams.product.$original().id}/units/quantities` )
                .subscribe( result => {
                    
                    if ( result.length === 0 ) {
                        this.$popup.close();
                        return nsSnackBar.error( __( 'This product doesn\'t have any unit defined for selling.' ) ).subscribe();
                    }

                    this.unitsQuantities  =   result;

                    /**
                     * This will automatically
                     * select a unit if there is only one unit available.
                     */
                    if ( this.unitsQuantities.length === 1 ) {
                        this.selectUnit( this.unitsQuantities[0] );
                    }
                })
        },
        /**
         * we'll resolve a value that
         * will be added to the object
         * built at the end
         * @param Unit
         */
        selectUnit( unitQuantity ) {
            this.$popupParams.resolve({
                unit_quantity_id    :   unitQuantity.id,
                unit_name           :   unitQuantity.unit.name,
                $quantities         :   () => unitQuantity
            });
            this.$popup.close();
        }
    }
}
</script>