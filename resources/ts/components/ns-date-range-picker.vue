<script lang="ts">
import Vue from 'vue';
import moment from "moment";
import DateRangePicker from 'vue2-daterange-picker';
import 'vue2-daterange-picker/dist/vue2-daterange-picker.css';
import { __ } from '@/libraries/lang';

declare const ns;

export default Vue.extend({
    name: 'ns-date-range-picker',
    data: () => {
        return {
            dateRange: {
                startDate: null,
                endDate: null,
            }
        }
    },
    components: {
        DateRangePicker
    },
    mounted() {
        if ( this.field.value !== undefined ) {
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
        }
    },
    props: [ 'placeholder', 'leading', 'type', 'field' ],
})
</script>
<template>
    <div class="flex flex-auto flex-col mb-2">
        <label :for="field.name" :class="hasError ? 'text-red-700' : 'text-gray-700'" class="block leading-5 font-medium"><slot></slot></label>
        <div :class="hasError ? 'border-red-400' : 'border-gray-200'" class="mt-1 relative flex border-2 rounded-md focus:shadow-sm">
            <div v-if="leading" class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                <span class="text-gray-500 sm:text-sm sm:leading-5">
                {{ leading }}
                </span>
            </div>
            <button class="px-3 outline-none bg-red-500 font-semibold text-white" @click="clearDate()">
                <i class="las la-times"></i>
            </button>
            <date-range-picker
                class="w-full flex items-center"
                ref="picker"
                :locale-data="{ firstDay: 1, format: 'yyyy-mm-dd HH:mm:ss' }"
                :timePicker="true"
                :timePicker24Hour="true"
                :showWeekNumbers="true"
                :showDropdowns="true"
                :autoApply="false"
                :appendToBody="true"
                v-model="dateRange"
                :disabled="field.disabled" 
                :linkedCalendars="true"
                >
                <!-- :dateFormat="dateFormat" -->
                <template v-slot:input="picker" class="w-full">
                    <div class="flex justify-between items-center w-full py-2">
                        <span class="text-xs">{{ __( 'Range Starts' ) }} : {{ getFormattedDate( picker.startDate ) }}</span>
                        <span class="text-xs">{{ __( 'Range Ends' ) }} : {{ getFormattedDate( picker.endDate ) }}</span>
                    </div>
                </template>
            </date-range-picker>
        </div>
        <p v-if="! field.errors || field.errors.length === 0" class="text-xs text-gray-500"><slot name="description"></slot></p>
        <p :key="index" v-for="(error, index) of field.errors" class="text-xs text-red-400">
            <slot v-if="error.identifier === 'required'" :name="error.identifier">{{ __( 'This field is required.' ) }}</slot>
            <slot v-if="error.identifier === 'email'" :name="error.identifier">{{ __( 'This field must contain a valid email address.' ) }}</slot>
            <slot v-if="error.identifier === 'invalid'" :name="error.identifier">{{ error.message }}</slot>
        </p>
    </div>
</template>