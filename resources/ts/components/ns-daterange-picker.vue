<script lang="ts">
import moment from "moment";
import DateRangePicker from 'vue2-daterange-picker';
import 'vue2-daterange-picker/dist/vue2-daterange-picker.css';
import { __ } from '~/libraries/lang';

declare const ns;

export default {
    name: 'ns-date-range-picker',
    data() {
        return {
            dateRange: {
                startDate: null,
                endDate: null,
            },
            rangeViewToggled: false,
        }
    },
    components: {
        DateRangePicker
    },
    mounted() {
        if ( this.field.value !== undefined && this.field.value.startDate && this.field.value.endDate ) {
            this.dateRange    =   this.field.value;
        }
    },
    watch: {
        dateRange() {
            const value     =   {
                startDate: moment( this.dateRange.startDate ).format( 'YYYY-MM-DD HH:mm' ),
                endDate: moment( this.dateRange.endDate ).format( 'YYYY-MM-DD HH:mm' ),
            };

            this.field.value    =   value;
            
            this.$emit( 'change', this );
        }
    },
    methods: {
        __,
        getFormattedDate( date ) {
            return date !== null ? moment( date ).format( 'YYYY-MM-DD HH:mm' ) : __( 'N/D' );
        },
        clearDate() {
            this.dateRange      =   {
                startDate: null,
                endDate: null
            };
            this.field.value    =   undefined;
        },
        toggleRangeView() {
            this.rangeViewToggled = ! this.rangeViewToggled;
        }
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
            return moment( this.field.value.startDate ).format( 'YYYY-MM-DD HH:mm' );
        },
        endDateFormatted() {
            return moment( this.field.value.endDate ).format( 'YYYY-MM-DD HH:mm' );
        }
    },
    props: [ 'placeholder', 'leading', 'type', 'field' ],
}
</script>
<template>
    <div class="flex flex-auto flex-col mb-2 ns-date-range-picker">
        <label :for="field.name" :class="hasError ? 'text-error-primary' : 'text-primary'" class="block leading-5 font-medium"><slot></slot></label>
        <div :class="hasError ? 'error' : ''" class="mt-1 relative flex input-group shadow rounded overflow-hidden focus:shadow-sm bg-input-background border-input-edge">
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
            <button class="px-3 outline-none bg-error-secondary font-semibold text-white" @click="clearDate()">
                <i class="las la-times"></i>
            </button>
        </div>
        <div class="relative h-0 w-0" v-if="rangeViewToggled">
            <div class="w-120 mt-2 shadow anim-duration-300 zoom-in-entrance flex flex-col">
                <div class="flex bg-input-background rounded-lg">
                    <ns-calendar @set="field.value.startDate = $event" :date="field.startDate"></ns-calendar>
                    <div class="px-2 border-l border-r border-box-background"></div>
                    <ns-calendar @set="field.value.endDate = $event" :date="field.endDate"></ns-calendar>
                </div>
            </div>
        </div>
        <p v-if="! field.errors || field.errors.length === 0" class="text-xs text-gray-500"><slot name="description"></slot></p>
        <p :key="index" v-for="(error, index) of field.errors" class="text-xs text-red-400">
            <slot v-if="error.identifier === 'required'" :name="error.identifier">{{ __( 'This field is required.' ) }}</slot>
            <slot v-if="error.identifier === 'email'" :name="error.identifier">{{ __( 'This field must contain a valid email address.' ) }}</slot>
            <slot v-if="error.identifier === 'invalid'" :name="error.identifier">{{ error.message }}</slot>
        </p>
    </div>
</template>