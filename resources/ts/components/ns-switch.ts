import { __ } from '@/libraries/lang';
import Vue from 'vue';

const nsSwitch      =   Vue.component( 'ns-switch', {
    data: () => {
        return {
        }
    },
    mounted() {
    },
    computed: {
        _options() {
            return this.field.options.map( option => {
                option.selected     =   option.value    === this.field.value;
                return option;
            })
        },
        hasError() {
            if ( this.field.errors !== undefined && this.field.errors.length > 0 ) {
                return true;
            }
            return false;
        },
        disabledClass() {
            return this.field.disabled ? 'bg-gray-200 cursor-not-allowed' : '';
        },
        sizeClass() {
            return ` w-1/${this._options.length <= 4 ? this._options.length : 4}`;
        },
        inputClass() {
            return this.disabledClass + ' ' + this.leadClass;
        },
        leadClass() {
            return this.leading ? 'pl-8' : 'px-4';
        }
    },
    methods: {
        __,
        setSelected( option ) {
            this.field.value    =   option.value;
            this._options.forEach( option => option.selected = false );
            option.selected     =   true;
            this.$forceUpdate();
            this.$emit( 'change', option.value );
        }
    },
    props: [ 'placeholder', 'leading', 'type', 'field' ],
    template: `
    <div class="flex flex-col mb-2">
        <label :for="field.name" :class="hasError ? 'text-red-700' : 'text-gray-700'" class="block leading-5 font-medium"><slot></slot></label>
        <div class="rounded-lg flex overflow-hidden w-40 shadow my-2" :class="hasError ? 'border border-red-400' : ''">
            <button 
                :disabled="option.disabled" 
                v-for="option of _options" 
                @click="setSelected( option )" 
                :class="option.selected ? 'bg-blue-400 text-white ' + sizeClass : 'bg-gray-100 text-gray-800' + ' ' + inputClass + ' ' + sizeClass" 
                class="px-3 py-2 flex-no-wrap outline-none">{{ option.label }}</button>
        </div>
        <p v-if="! field.errors || field.errors.length === 0" class="text-xs text-gray-500"><slot name="description"></slot></p>
        <p v-for="error of field.errors" class="text-xs text-red-400">
            <slot v-if="error.identifier === 'required'" :name="error.identifier">{{ __( 'This field is required.' ) }}</slot>
        </p>
    </div>
    `,
});

export { nsSwitch }