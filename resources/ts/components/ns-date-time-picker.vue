<template>
    <div class="picker mb-2">
        <label v-if="field && field.label && field.label.length > 0" class="block leading-5 font-medium text-primary">{{ field.label }}</label>
        <div class="ns-button">
            <button @click="visible = !visible" :class="field && field.label && field.label.length > 0 ? 'mt-1 border border-input-edge' : ''" class="shadow rounded cursor-pointer w-full p-1 flex items-center text-primary">
                <i class="las la-clock text-xl"></i>
                <span class="mx-1 text-sm" v-if="field">
                    <span v-if="! [ null, '', undefined ].includes( field.value )">{{ fieldDate.format( 'YYYY-MM-DD HH:mm' ) }}</span>
                    <span v-if="[ null, '', undefined ].includes( field.value )">N/A</span>
                </span>
                <span class="mx-1 text-sm" v-if="date">
                    <span v-if="! [ null, '', undefined ].includes( date )">{{ fieldDate.format( 'YYYY-MM-DD HH:mm' ) }}</span>
                    <span v-if="[ null, '', undefined ].includes( date )">N/A</span>
                </span>
            </button>
        </div>
        <p class="text-sm text-secondary py-1" v-if="field">{{ field.description }}</p>
        <div class="relative z-10 h-0 w-0" v-if="visible">
            <div :class="field && field.label && field.label.length > 0 ? '-mt-4' : 'mt-2'" class="absolute w-72 shadow-xl rounded ns-box anim-duration-300 zoom-in-entrance flex flex-col">
                <template v-if="field">
                    <ns-calendar 
                        @onClickOut="visible = false"
                        @set="setDate( $event )"
                        :visible="visible" 
                        :date="field.value"></ns-calendar>
                </template>
                <template v-if="date">
                    <ns-calendar 
                        @onClickOut="visible = false"
                        @set="setDate( $event )"
                        :visible="visible" 
                        :date="date"></ns-calendar>
                </template>
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
            if ( this.field ) {
                return moment( this.field.value ).isValid() ? moment( this.field.value ) : moment();
            } else if( this.date ) {
                return moment( this.date );
            } else {
                return moment();
            }
        }
    },
    mounted() {
        let currentMoment = moment( this.field.value );

        if ( currentMoment.isValid() ) {
            this.setDate( currentMoment.format( 'YYYY-MM-DD HH:mm:ss' ) );
        } else {
            this.setDate( moment( ns.date.current ).format( 'YYYY-MM-DD HH:mm:ss' ) );
        }
    },
    methods: {
        __,
        setDate( date ) {
            this.field.value    =   date;
        }
    }
}
</script>
