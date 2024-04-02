<template>
    <div @click="voidOngoingOrder( order )" id="void-button" class="flex-shrink-0 w-1/4 flex items-center font-bold cursor-pointer justify-center bg-red-500 text-white border-box-edge hover:bg-red-600 flex-auto">
        <i class="mr-2 text-2xl lg:text-xl las la-trash"></i> 
        <span class="text-lg hidden md:inline lg:text-2xl">{{ __( 'Void' ) }}</span>
    </div>
</template>
<script>
export default {
    props: [ 'order', 'settings' ],
    methods: {
        __,
        voidOngoingOrder() {
            POS.voidOrder( this.order );
        },
    }
}
</script>