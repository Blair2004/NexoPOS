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
    <div class="flex ns-button rounded overflow-hidden" :class="type ? ( 'ns-button-' + type ) : 'ns-button-default'" @click="$emit( 'click' )">
        <button :disabled="isDisabled" v-if="!link" :class="buttonclass" class="flex rounded items-center cursor-pointer py-2 px-3 font-semibold"><slot></slot></button>
        <router-link  :to="to" v-if="routerLink" :class="buttonclass" class="flex rounded items-center cursor-pointer py-2 px-3 font-semibold"><slot></slot></router-link>
        <a v-if="link" :href="href" :class="buttonclass" class="flex rounded items-center cursor-pointer py-2 px-3 font-semibold"><slot></slot></a>
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
                    className = `${this.isDisabled ? 'ns-disabled' : 'shadow ns-enabled' }`;
                break;
                case 'success':
                    className = `${this.isDisabled ? 'ns-disabled' : 'shadow ns-enabled' }`;
                break;
                case 'danger':
                    className = `${this.isDisabled ? 'ns-disabled' : 'shadow ns-enabled' }`;
                break;
                case 'warning':
                    className = `${this.isDisabled ? 'ns-disabled' : 'shadow ns-enabled' }`;
                break;
                default:
                    className = `${this.isDisabled ? 'ns-disabled' : 'shadow ns-enabled' }`;
                break;
            }

            return className;
        }
    }
});

export { nsButton };