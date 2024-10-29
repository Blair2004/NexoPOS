<template>
    <div class="w-6/7-screen md:w-4/7-screen lg:w-3/7-screen flex flex-col shadow-lg ns-box">
        <div class="ns-box-header p-2 border-b flex justify-between items-center">
            <h3 class="font-bold text-xl">{{ __( 'Expense Type' ) }}</h3>
        </div>
        <div class="p-2" v-if="warningMessage">
            <ns-notice color="info">
                <template #title>{{ __( 'Warning' ) }}</template>
                <p v-html="warningMessage"></p>
            </ns-notice>
        </div>
        <div class="grid grid-cols-2">
            <div :class="type === configuration.identifier ? 'info' : ''" @click="selectType( configuration )" class="h-40 elevation-surface hoverable flex-col flex items-center justify-center cursor-pointer" :key="configuration.identifier" v-for="configuration of configurations">
                <img :src="configuration.icon" class="w-20 my-2" :alt="configuration.label">
                <h3 class="font-bold">{{ configuration.label }}</h3>
            </div>
        </div>
    </div>
</template>
<script>
import { nsCurrency } from '~/filters/currency';
import { __ } from '~/libraries/lang';
import popupCloser from '~/libraries/popup-closer';
import popupResolver from '~/libraries/popup-resolver';

export default {
    name: 'ns-transaction-selector',
    props: [ 'popup' ],
    data() {
        return {
            configurations: [],
            warningMessage: false,
            type: null
        }
    },
    mounted() {
        this.configurations     =   this.popup.params.configurations;
        this.warningMessage     =   this.popup.params.warningMessage;
        this.type   =   this.popup.params.type
    },
    methods: {
        __,
        nsCurrency,
        popupResolver,
        popupCloser,
        selectType( configuration ) {
            this.popupResolver( configuration );
        }
    }
}
</script>