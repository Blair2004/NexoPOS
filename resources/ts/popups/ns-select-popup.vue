<template>
    <div class="shadow-xl bg-surface-tertiary w-6/7-screen md:w-4/7-screen lg:w-3/7-screen overflow-hidden">
        <div class="p-2 flex justify-between border-b border-surface-secondary">
            <span class="text-semibold text-primary">
                {{ label }}
            </span>
            <div>
                <ns-close-button @click="close()"></ns-close-button>
            </div>
        </div>
        <div class="flex-auto overflow-y-auto">
            <ul>
                <template v-if="type === 'select'">
                    <li @click="select( option )" class="p-2 border-b border-surface-secondary text-primary cursor-pointer hover:bg-surface-secondary" v-for="option of options" :key="option.value">{{ option.label }}</li>
                </template>
                <template v-if="type === 'multiselect'">
                    <li @click="toggle(option)" :class="isSelected( option ) ? 'bg-surface-secondary border-surface-secondary' : 'border-surface-secondary'" class="p-2 border-b text-primary cursor-pointer hover:bg-surface-secondary" v-for="option of options" :key="option.value">{{ option.label }}</li>
                </template>
            </ul>
        </div>
        <div class="flex justify-between" v-if="type === 'multiselect'">
            <div></div>
            <div>
                <ns-button @click="select()" type="info">{{ __( 'Select' ) }}</ns-button>
            </div>
        </div>
    </div>
</template>
<script>
import popupCloser from "@/libraries/popup-closer";
import { __ } from '@/libraries/lang';
export default {
    data() {
        return {
            value: [],
            options: [],
            label: null,
            type: 'select'
        }
    },
    computed: {
    },
    mounted() {
        this.popupCloser();
        this.value      =   this.$popupParams.value  || [];
        this.options    =   this.$popupParams.options;
        this.label      =   this.$popupParams.label;
        this.type       =   this.$popupParams.type || this.type;
    },
    methods: {
        popupCloser,
        __,

        toggle( option ) {
            const index     =   this.value.indexOf( option );

            if ( index === -1 ) {
                this.value.unshift( option );
            } else {
                this.value.splice( index, 1 );
            }
        },

        isSelected( option ) {
            return this.value.indexOf( option ) >= 0;
        },

        close() {
            this.$popupParams.reject( false );
            this.$popup.close();
        },

        select( option ) {
            if ( option !== undefined ) {
                this.value  =   [ option ];
            }

            this.$popupParams.resolve( this.value );
            this.close();
        }
    }
}
</script>