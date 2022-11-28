<template>
    <div class="flex card-widget flex-auto flex-col rounded-lg shadow-lg bg-gradient-to-br from-indigo-400 to-indigo-600 px-3 py-5">
        <div class="flex flex-row md:flex-col flex-auto">
            <div class="w-1/2 md:w-full flex md:flex-col md:items-start items-center justify-center">
                <div class="flex justify-between w-full items-center">
                    <h6 class="font-bold hidden text-right md:inline-block">{{ __( 'Expenses' ) }}</h6>
                    <div>
                        <ns-close-button class="border-gray-400" @click="$emit( 'onRemove' )"></ns-close-button>
                    </div>
                </div>
                <h3 class="text-2xl font-black">
                    {{ nsCurrency(report.total_expenses || 0, 'abbreviate' ) }}
                </h3>
            </div>
            <div class="w-1/2 md:w-full flex flex-col px-2 justify-end items-end">
                <h6 class="font-bold inline-block text-right md:hidden">{{ __( 'Expenses' ) }}</h6>
                <h4 class="text-xs text-right">+{{ nsCurrency( report.day_expenses || 0 ) }} {{ __( 'Today' ) }}</h4>
            </div>
        </div>
    </div>
</template>
<script>
import { nsCurrency } from '~/filters/currency';
import { __ } from '~/libraries/lang';

export default {
    name: 'ns-sale-card-widget',
    methods: {
        __,
        nsCurrency,
    },
    data() {
        return {
            report: {},
        }
    },
    mounted() {
        Dashboard.day.subscribe( result => {
            this.report     =   result;
        })
    }
}
</script>