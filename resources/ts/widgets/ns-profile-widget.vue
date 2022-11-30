<template>
    <div id="ns-best-cashiers" class="flex flex-auto flex-col shadow rounded-lg overflow-hidden">
        <div class="flex-auto">
            <div class="head text-center border-b w-full flex justify-between items-center p-2">
                <h5>{{ __( 'Profile' ) }}</h5>
                <div class="flex -mx-1">
                    <div class="px-1">
                        <ns-icon-button class-name="la-sync-alt" @click="loadUserProfileWidget(true)"></ns-icon-button>
                    </div>
                    <div class="px-1">
                        <ns-close-button @click="$emit( 'onRemove' )"></ns-close-button>
                    </div>
                </div>
            </div>
            <div class="body">
                <div class="h-40 flex items-center justify-center">
                    <div class="rounded-full border-4 border-gray-400 bg-white shadow-lg overflow-hidden">
                        <ns-avatar-image  :size="32" :url="user.attributes.avatar_link" :name="user.username"></ns-avatar-image>
                    </div>
                </div>
                <div class="border-t border-box-edge">
                    <ul>
                        <li v-for="(detail, key) of profileDetails" :key="key" class="border-b border-box-edge p-2 flex justify-between">
                            <span>{{ detail.label }}</span>
                            <span>{{ detail.value }}</span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</template>
<script>
import nsAvatarImage from '~/components/ns-avatar-image.vue';
import { nsCurrency } from '~/filters/currency';
import { __ } from '~/libraries/lang';

export default {
    name: 'ns-profile-widget',
    components: { nsAvatarImage },
    data() {
        return {
            svg: '',
            user: ns.user,
            profileDetails: [],
        }
    },
    computed: {
        avatarUrl() {
            return this.url.length === 0 ? '' : this.url;
        }
    },
    mounted() {
        this.loadUserProfileWidget();
    },
    methods: {
        __,
        nsCurrency,
        loadUserProfileWidget( refresh ) {
            nsHttpClient.get( `/api/reports/cashier-report${refresh ? '?refresh=true' : ''}` ).subscribe( result => {
                this.profileDetails     =   result;
            })
        }
    }
}
</script>