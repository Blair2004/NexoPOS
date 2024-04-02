<script lang="ts">
import moment from "moment";
import DateRangePicker from 'vue3-daterange-picker';
// import 'vue3-daterange-picker/dist/vue3-daterange-picker.css';
import { __ } from '~/libraries/lang';

declare const ns;

export default {
    name: 'ns-date-range-picker',
    data() {
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
}
</script>
<template>
    <div class="flex flex-auto flex-col mb-2 ns-date-range-picker">
        <label :for="field.name" :class="hasError ? 'text-error-primary' : 'text-primary'" class="block leading-5 font-medium"><slot></slot></label>
        <div :class="hasError ? 'error' : ''" class="mt-1 relative flex input-group border-2 rounded-md overflow-hidden focus:shadow-sm">
            <div v-if="leading" class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                <span class="text-primary sm:text-sm sm:leading-5">
                {{ leading }}
                </span>
            </div>
            <button class="px-3 outline-none bg-error-secondary font-semibold text-white" @click="clearDate()">
                <i class="las la-times"></i>
            </button>
            <date-range-picker
                class="w-full flex items-center bg-input-background"
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
        <ns-field-description :field="field"></ns-field-description>
    </div>
</template>