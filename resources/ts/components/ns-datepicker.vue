<template>
    <div class="picker">
        <div class="ns-button">
            <button @click="visible = !visible" class="rounded cursor-pointer border border-input-edge shadow w-full px-1 py-1 flex items-center text-primary">
                <i class="las la-clock text-2xl"></i>
                <span class="mx-1 text-sm">
                    <span>{{ label || __( 'Date' ) }} : </span>
                    <span v-if="date">{{ formattedDate }}</span>
                    <span v-else>{{ __( 'N/A' ) }}</span>
                </span>
            </button>
        </div>
        <div class="relative h-0 w-0 -mb-2" v-if="visible">
            <div class="w-72 mt-2 shadow-lg anim-duration-300 zoom-in-entrance flex flex-col ns-floating-panel">
                <ns-calendar :visible="visible" @onClickOut="visible = false" :date="date" @set="setDate( $event )"></ns-calendar>
            </div>
        </div>
    </div>
</template>
<script>
import moment from 'moment';
import { __ } from '~/libraries/lang';
import nsCalendar from './ns-calendar.vue';

export default {
    name: "ns-datepicker",
    components: {
        nsCalendar
    },
    props: [ 'label', 'date', 'format' ],
    computed: {
        formattedDate() {
            return moment( this.date ).format( this.format || 'YYYY-MM-DD HH:mm:ss' );
        }
    },
    data() {
        return {
            visible: false,                      
        }
    },
    mounted() {
        
    },
    methods: {
        __,
        setDate( date ) {
            this.$emit( 'set', date );
        }
    }
}
</script>
