<template>
    <div class="h-full w-full">
        <h1>Quantity Popup</h1>
    </div>
</template>
<script>
export default {
    data() {
        return {
            types: []
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
        select( type ) {
            this.types.forEach( type => type.selected = false );
            type.selected   =   true;
            POS.order.types.next( this.types );
            this.$popup.close();
        }
    }
}
</script>