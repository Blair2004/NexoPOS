<template>
    <div class="picker mb-2">
        <label v-if="field" class="block leading-5 font-medium text-primary">{{ field.label }}</label>
        <div class="ns-button">
            <button @click="visible = !visible" :class="field ? 'mt-1' : ''" class="border border-input-edge shadow rounded cursor-pointer w-full px-1 py-1 flex items-center text-primary">
                <i class="las la-clock text-2xl"></i>
                <span class="mx-1 text-sm" v-if="field">
                    <span v-if="field.value !== null">{{ fieldDate.format( 'YYYY-MM-DD HH:mm' ) }}</span>
                    <span v-if="field.value === null">N/A</span>
                </span>
            </button>
        </div>
        <p class="text-sm text-secondary py-1" v-if="field">{{ field.description }}</p>
        <div class="relative z-10 h-0 w-0" v-if="visible">
            <div :class="field ? '-mt-4' : 'mt-2'" class="absolute w-72 shadow-xl rounded ns-box anim-duration-300 zoom-in-entrance flex flex-col">
                <ns-calendar 
                    @onClickOut="visible = false"
                    @set="setDate( $event )"
                    :visible="visible" 
                    :date="field.value"></ns-calendar>
            </div>
        </div>
    </div>
</template>
<script>
import moment from "moment";
import { __ } from "~/libraries/lang";
export default {
    name: 'ns-date-time-picker',
    props: [ 'field', 'date' ],
    data() {
        return {
            visible: false,
            hours: 0,
            minutes: 0,
            currentView: 'days',
            currentDay: undefined,
            moment,
        }
    },
    computed: {
        fieldDate() {
            return moment( this.field.value );
        }
    },
    mounted() {
        // ... 
    },
    methods: {
        __,
        setDate( date ) {
            this.field.value = date;
        }
    }
}
</script>