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
    <div class="flex ns-button" :class="type ? type : 'default'" @click="$emit( 'click' )">
        <button :disabled="isDisabled" v-if="!link && !href" class="flex rounded items-center cursor-pointer py-2 px-3 font-semibold"><slot></slot></button>
        <router-link  :to="to" v-if="routerLink" class="flex rounded items-center cursor-pointer py-2 px-3 font-semibold"><slot></slot></router-link>
        <a v-if="href" :href="href" class="flex rounded items-center cursor-pointer py-2 px-3 font-semibold"><slot></slot></a>
    </div>
    `,
    mounted() {
        
    },
    computed: {
        isDisabled() {
            return this.disabled && ( this.disabled.length === 0 || this.disabled === 'disabled' || this.disabled );
        },
    }
});

export { nsButton };