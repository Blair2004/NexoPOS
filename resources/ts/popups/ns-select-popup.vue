<template>
    <div class="shadow-xl ns-box w-6/7-screen md:w-4/7-screen lg:w-3/7-screen max-h-5/6-screen overflow-hidden flex flex-col">
        <div class="p-2 flex justify-between border-b items-center ns-box-header">
            <span class="text-semibold text-primary">
                {{ label }}
            </span>
            <div>
                <ns-close-button @click="close()"></ns-close-button>
            </div>
        </div>
        <div class="flex flex-col overflow-hidden">
            <p class="p-2 text-center text-sm bg-info-primary" v-if="description.length > 0">{{ description }}</p>
            <div class="m-2 border-dashed border-box-edge border-b">
                <ns-field @keypress.enter="quickSelect()" :field="field"></ns-field>
            </div>
            <div class="overflow-y-auto">
                <ul class="ns-vertical-menu">
                    <template v-if="type === 'select'">
                        <li @click="select( option )" class="p-2 border-b border-box-edge text-primary cursor-pointer" v-for="option of filtredOptions" :key="option.value">
                            <span>{{ option.label }}</span>
                        </li>
                    </template>
                    <template v-if="type === 'multiselect'">
                        <li @click="toggle(option)" :class="isSelected( option ) ? 'active' : ''" class="p-2 border-b text-primary cursor-pointer flex justify-between" v-for="option of filtredOptions" :key="option.value">
                            <span>{{ option.label }}</span>
                            <span v-if="isSelected( option )"><i class="las la-check"></i></span>
                        </li>
                    </template>
                </ul>
            </div>
        </div>
        <div class="flex justify-between p-2" v-if="type === 'multiselect'">
            <div></div>
            <div>
                <ns-button @click="select()" type="info">{{ __( 'Select' ) }}</ns-button>
            </div>
        </div>
    </div>
</template>
<script lang="ts">
import popupCloser from "~/libraries/popup-closer";
import { __ } from '~/libraries/lang';
export default {
    name: 'ns-select-popup',
    props: [ 'popup' ],
    data() {
        return {
            value: [],
            options: [],
            description: '',
            label: null,
            type: 'select',
            field: {
                name: 'search',
                placeholder: __( 'Search for options' ),
                value: '',
                type: 'text'
            }
        }
    },
    computed: {
        filtredOptions() {
            let result  =   this.options.filter( option => {
                const reg   = new RegExp( this.field.value, 'i' );
                return this.field.value.length === 0 ? true : reg.test( option.label );
            });

            return result;
        }
    },
    mounted() {
        this.popupCloser();
        this.value       =   this.popup.params.value  || [];
        this.options     =   this.popup.params.options;
        this.label       =   this.popup.params.label;
        this.description =   this.popup.params.description || '';
        this.type        =   this.popup.params.type || this.type;
    },
    methods: {
        popupCloser,
        __,

        toggle( option ) {
            if ( ! this.value.includes( option.value ) ) {
                this.value.unshift( option.value );
            } else {
                const indexOf   =   this.value.indexOf( option.value );
                this.value.splice( indexOf, 1 );
            }
        },

        isSelected( option ) {
            return this.value.includes( option.value );
        },

        close() {
            this.popup.params.reject( false );
            this.popup.close();
        },

        quickSelect() {
            if ( this.filtredOptions.length === 1 ) {
                this.select( this.filtredOptions[0] );
            }
        },

        select( option = undefined ) {
            if ( option !== undefined ) {
                this.value  =   [ option.value ];
            }

            this.popup.params.resolve( this.type === 'select' ? this.value[0] : this.value );
            this.close();
        }
    }
}
</script>