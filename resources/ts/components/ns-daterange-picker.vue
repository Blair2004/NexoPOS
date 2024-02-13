<script lang="ts">
import moment from "moment";
import { __ } from '~/libraries/lang';

declare const ns;

export default {
    name: 'ns-daterange-picker',
    data() {
        return {
            leftCalendar: moment(),
            rightCalendar: moment().add( 1, 'months' ),
            rangeViewToggled: false,
            clickedOnCalendar: false,
        }
    },
    mounted() {
        if ( ! this.field.value ) {
            this.clearDate();
        }
        document.addEventListener( 'click', this.checkClickedItem );
    },
    beforeUnmount() {
        document.removeEventListener( 'click', this.checkClickedItem );
    },
    watch: {
        leftCalendar() {
            if ( this.leftCalendar.isSame( this.rightCalendar, 'month' ) ) {
                this.rightCalendar.add( 1, 'months' );
            } 
        },
        rightCalendar() {
            if ( this.rightCalendar.isSame( this.leftCalendar, 'month' ) ) {
                this.leftCalendar.sub( 1, 'months' );
            } 
        }
    },
    methods: {
        __,
        setDateRange( range, value ) {
            this.field.value[ range ]   =    value;

            if( moment( this.field.value.startDate ).isBefore( moment( this.field.value.endDate ) ) ) {
                this.$emit( 'change', this.field );
            }
        },
        getFormattedDate( date ) {
            return date !== null ? moment( date ).format( 'YYYY-MM-DD HH:mm' ) : __( 'N/D' );
        },
        clearDate() {
            this.field.value    =   {
                startDate: null,
                endDate: null,
            };
        },
        toggleRangeView() {
            this.rangeViewToggled = ! this.rangeViewToggled;
        },
        handleDateRangeClick() {
            this.clickedOnCalendar  =   true;
        },
        checkClickedItem( event ) {            
            /**
             * this short debounce will make sure
             * while switching view, the popup doesn't close
             */
            if ( this.$el.getAttribute( 'class' ).split( ' ' ).includes( 'ns-daterange-picker' ) ) {
                let clickChildrens        =   this.$el.contains( event.srcElement );
                
                if ( ! clickChildrens && ! this.clickedOnCalendar && this.rangeViewToggled ) {
                    this.$emit( 'blur', this.field );
                    this.toggleRangeView();
                }

                /**
                 * This debounce will make sure
                 * the popup will be closed if  the next click
                 * is not on the calendar.
                 */
                setTimeout( () => {
                    this.clickedOnCalendar = false;
                }, 100 );
            }
        },
    },
    computed: {
        hasError() {
            if ( this.field.errors !== undefined && this.field.errors.length > 0 ) {
                return true;
            }
            return false;
        },
        disabledClass() {
            return this.field.disabled ? 'bg-gray-200 cursor-not-allowed' : 'bg-transparent';
        },
        inputClass() {
            return this.disabledClass + ' ' + this.leadClass
        },
        leadClass() {
            return this.leading ? 'pl-8' : 'px-4';
        },
        startDateFormatted() {
            return this.field.value !== undefined && moment( this.field.value.startDate ).isValid() ? moment( this.field.value.startDate ).format( 'YYYY-MM-DD HH:mm' ) : false;
        },
        endDateFormatted() {
            return this.field.value !== undefined && moment( this.field.value.endDate ).isValid() ? moment( this.field.value.endDate ).format( 'YYYY-MM-DD HH:mm' ) : false;
        }
    },
    props: [ 'placeholder', 'leading', 'type', 'field' ],
}
</script>
<template>
    <div @click="handleDateRangeClick()" class="flex flex-auto flex-col mb-2 ns-daterange-picker">
        <label :for="field.name" :class="hasError ? 'text-error-primary' : 'text-primary'" class="block leading-5 font-medium"><slot></slot></label>
        <div :class="hasError ? 'error' : ''" class="mt-1 relative flex input-group bg-input-background rounded overflow-hidden shadow  focus:shadow-sm">
            <div class="border border-input-edge rounded-tl rounded-bl flex-auto flex">
                <div v-if="leading" class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <span class="text-primary sm:text-sm sm:leading-5">
                    {{ leading }}
                    </span>
                </div>
                <div class="flex flex-auto p-1 text-primary text-sm items-center cursor-pointer" @click="toggleRangeView()">
                    <span class="mr-1"><i class="las la-clock text-2xl"></i></span>
                    <span class="">{{ startDateFormatted || __( 'N/A' ) }}</span>
                    <span class="mx-2">&mdash;</span>
                    <span class="mr-1"><i class="las la-clock text-2xl"></i></span>
                    <span class=""> {{ endDateFormatted || __( 'N/A' ) }}</span>
                </div>
            </div>
            <button class="px-3 outline-none font-bold bg-error-tertiary" @click="clearDate()">
                <i class="las la-times"></i>
            </button>
        </div>
        <div class="relative h-0 w-0" v-if="rangeViewToggled">
            <div class="z-10 absolute md:w-[550px] w-[225px] mt-2 shadow-lg anim-duration-300 zoom-in-entrance flex flex-col">
                <div class="flex flex-col md:flex-row bg-box-background rounded-lg">
                    <ns-calendar 
                        class="md:w-1/2 w-full"
                        :range="[startDateFormatted,endDateFormatted]"
                        :side="'left'"
                        :date="field.value.startDate"
                        :selected-range="field.value"
                        @set="setDateRange( 'startDate', $event )" 
                        ></ns-calendar>
                    <div class="flex-auto border-l border-r"></div>
                    <ns-calendar 
                        class="md:w-1/2 w-full"
                        :range="[startDateFormatted,endDateFormatted]" 
                        :side="'right'"
                        :date="field.value.endDate"
                        :selected-range="field.value"
                        @set="setDateRange( 'endDate', $event )" 
                        ></ns-calendar>
                </div>
            </div>
        </div>
        <ns-field-description :field="field"></ns-field-description>
    </div>
</template>