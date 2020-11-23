import Vue from 'vue';

const nsButton      =   Vue.component( 'ns-button', {
    data: () => {
        return {
            clicked: false,
            _save: 0
        }
    },
    props: [ 'type', 'disabled', 'link', 'href', 'routerLink', 'to' ],
    template: `
    <div class="flex" @click="$emit( 'click' )">
        <button :disabled="isDisabled" v-if="!link" :class="buttonclass" class="flex rounded cursor-pointer py-2 px-3 font-semibold"><slot></slot></button>
        <router-link  :to="to" v-if="routerLink" :class="buttonclass" class="flex rounded cursor-pointer py-2 px-3 font-semibold"><slot></slot></router-link>
        <a v-if="link" :href="href" :class="buttonclass" class="flex rounded cursor-pointer py-2 px-3 font-semibold"><slot></slot></a>
    </div>
    `,
    mounted() {
        
    },
    computed: {
        isDisabled() {
            return this.disabled && ( this.disabled.length === 0 || this.disabled === 'disabled' || this.disabled );
        },

        buttonclass() {
            let className;

            switch( this.type ) {
                case 'info':
                    className = `${this.isDisabled ? 'bg-gray-400 border border-gray-500 cursor-not-allowed text-gray-600' : 'shadow bg-blue-400 text-white' }`;
                break;
                case 'success':
                    className = `${this.isDisabled ? 'bg-gray-400 border border-gray-500 cursor-not-allowed text-gray-600' : 'shadow bg-green-400 text-white' }`;
                break;
                case 'danger':
                    className = `${this.isDisabled ? 'bg-gray-400 border border-gray-500 cursor-not-allowed text-gray-600' : 'shadow bg-red-400 text-white' }`;
                break;
                case 'warning':
                    className = `${this.isDisabled ? 'bg-gray-400 border border-gray-500 cursor-not-allowed text-gray-600' : 'shadow bg-orange-400 text-white' }`;
                break;
                default:
                    className = `${this.isDisabled ? 'bg-gray-400 border border-gray-500 cursor-not-allowed text-gray-600' : 'shadow bg-white text-gray-800' }`;
                break;
            }

            return className;
        }
    }
});

export { nsButton };