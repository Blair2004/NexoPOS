<template>
    <div class="ns-box shadow-lg w-95vw h-95vh md:w-3/5-screen md:h-5/6-screen flex flex-col">
        <div class="p-2 border-b ns-box-header flex justify-between items-center">
            <h3>{{ __( 'Search Filters' ) }}</h3>
            <div>
                <ns-close-button @click="closePopup()"></ns-close-button>
            </div>
        </div>
        <div class="p-2 ns-box-body flex-auto">
            <ns-field :field="field" :key="index" v-for="( field, index ) of fields"></ns-field>
        </div>
        <div class="p-2 flex justify-between ns-box-footer border-t">
            <div>
                <ns-button @click="clearFilters()" type="error">{{ __( 'Clear Filters' ) }}</ns-button>
            </div>
            <div>
                <ns-button @click="useFilters()" type="info">{{ __( 'Use Filters' ) }}</ns-button>
            </div>
        </div>
    </div>
</template>
<script lang="ts">
import FormValidation from '~/libraries/form-validation';
import { __ } from '~/libraries/lang';
import popupCloser from '~/libraries/popup-closer';
import popupResolver from '~/libraries/popup-resolver';

export default {
    data() {
        return {
            fields: [],
            validation: new FormValidation
        }
    },
    props: [ 'popup' ],
    methods: {
        __,
        popupCloser,
        popupResolver,
        closePopup() {
            this.popupResolver( false );
        },
        useFilters() {
            this.popupResolver( this.validation.extractFields( this.fields ) );
        },
        clearFilters() {
            this.fields.forEach( field => field.value = '' );
            this.popupResolver( null );
        }
    },
    mounted() {
        this.fields     =   this.validation.createFields( this.popup.params.queryFilters );
        this.popupCloser();
    }
}
</script>