<template>
    <div id="ns-my-nexopos-widget" class="ns-box flex flex-auto flex-col overflow-hidden rounded-lg border border-box-edge bg-box-background text-fontcolor shadow">
        <div class="ns-box-header flex items-center justify-between border-b border-box-edge p-2">
            <div class="flex min-w-0 items-center gap-2">
                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded border border-info-secondary bg-info-primary text-info-tertiary">
                    <i class="las" :class="isConnected ? 'la-check-circle' : 'la-plug'"></i>
                </span>
                <div class="min-w-0">
                    <h5 class="truncate font-semibold">{{ __( 'My NexoPOS' ) }}</h5>
                    <p class="truncate text-xs text-fontcolor-soft">{{ isConnected ? __( 'Connected and ready' ) : __( 'Extend your store' ) }}</p>
                </div>
            </div>
            <div class="flex -mx-1">
                <div class="px-1">
                    <ns-icon-button class="widget-handle" className="la-expand-arrows-alt"></ns-icon-button>
                </div>
                <div class="px-1">
                    <ns-close-button @click="$emit( 'onRemove' )"></ns-close-button>
                </div>
            </div>
        </div>

        <div v-if="! isConnected" class="flex flex-auto flex-col gap-4 p-4">
            <div class="rounded border border-box-edge bg-box-elevation-background p-4">
                <div class="mb-3 flex items-center gap-2 text-info-tertiary">
                    <i class="las la-cloud text-2xl"></i>
                    <span class="text-xs font-semibold uppercase tracking-wider">{{ __( 'Connected features' ) }}</span>
                </div>
                <h3 class="text-lg font-bold leading-6 text-fontcolor-hard">
                    {{ __( 'Connect NexoPOS to a larger toolbox.' ) }}
                </h3>
                <p class="mt-2 text-sm leading-6 text-fontcolor-soft">
                    {{ __( 'Link this installation to my.nexopos.com to discover modules, install extensions faster, and keep your store ready for what comes next.' ) }}
                </p>
            </div>

            <div class="grid gap-2 text-sm">
                <div class="flex items-start gap-2">
                    <i class="las la-check-circle mt-0.5 text-success-tertiary"></i>
                    <span>{{ __( 'Browse the official NexoPOS marketplace from your dashboard.' ) }}</span>
                </div>
                <div class="flex items-start gap-2">
                    <i class="las la-check-circle mt-0.5 text-success-tertiary"></i>
                    <span>{{ __( 'Install and update extensions with a smoother authenticated flow.' ) }}</span>
                </div>
            </div>

            <div class="mt-auto flex flex-col items-center justify-between gap-3">
                <ns-button href="/dashboard/modules/marketplace?action=authenticate" type="info">
                    <span class="flex items-center justify-center gap-2">
                        <i class="las la-external-link-alt"></i>
                        <span>{{ __( 'Connect to My NexoPOS' ) }}</span>
                    </span>
                </ns-button>
                <ns-link href="/dashboard/modules/marketplace">
                    <span class="p-4">{{ __( 'Browse the marketplace' ) }}</span>
                </ns-link>
            </div>
        </div>

        <div v-else class="flex flex-auto flex-col gap-4 p-4">
            <div class="grid gap-3">
                <a
                    v-for="item of connectedSuggestions"
                    :key="item.title"
                    :href="item.href"
                    :target="item.external ? '_blank' : null"
                    class="rounded border border-box-edge bg-box-elevation-background p-3 text-fontcolor hover:border-info-secondary hover:bg-box-elevation-hover">
                    <div class="flex items-start gap-3">
                        <span class="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded border border-info-secondary bg-info-primary text-info-tertiary">
                            <i class="las text-lg" :class="item.icon"></i>
                        </span>
                        <div class="min-w-0">
                            <h4 class="font-semibold leading-5 text-fontcolor-hard">{{ __( item.title ) }}</h4>
                            <p class="mt-1 text-xs leading-5 text-fontcolor-soft">{{ __( item.description ) }}</p>
                        </div>
                    </div>
                </a>
            </div>

            <div class="mt-auto items-center flex flex-col gap-3">
                <ns-button href="/dashboard/modules/marketplace" type="info">
                    <span class="flex items-center justify-center gap-2">
                        <i class="las la-store-alt"></i>
                        <span>{{ __( 'Browse Modules' ) }}</span>
                    </span>
                </ns-button>
                <ns-link class="text-center text-xs text-fontcolor-soft hover:text-fontcolor" href="https://my.nexopos.com" target="_blank">
                    {{ __( 'Open My NexoPOS' ) }}
                </ns-link>
            </div>
        </div>
    </div>
</template>
<script>
import { __ } from '~/libraries/lang';

export default {
    name: 'ns-my-nexopos-widget',
    props: [ 'widget' ],
    data() {
        return {
            connectedSuggestions: [
                {
                    title: __( 'Discover business modules' ),
                    description: __( 'Add capabilities for restaurants, accounting, payments, reports, and specialized workflows.' ),
                    icon: 'la-cubes',
                    href: '/dashboard/modules/marketplace',
                    external: false,
                },
                {
                    title: __( 'Barcode Utility' ),
                    description: __( 'Barcode Utility turns your Android phone into a barcode reader that connects to a POS terminal.' ),
                    icon: 'la-mobile-alt',
                    href: 'https://my.nexopos.com/en/marketplace/item/barcode-utility-for-nexopos?utm_source=widget&utm_medium=link&utm_campaign=my-nexopos-widget',
                    external: true,
                },
                {
                    title: __( 'Secure sensitive actions' ),
                    description: __( 'Explore extensions that help protect privileged operations and keep cashier permissions focused.' ),
                    icon: 'la-shield-alt',
                    href: 'https://my.nexopos.com/en/marketplace/item/nexopos-authorizer?utm_source=widget&utm_medium=link&utm_campaign=my-nexopos-widget',
                    external: true,
                },
            ],
        }
    },
    computed: {
        isConnected() {
            return this.widget && this.widget.data && this.widget.data.isConnected === true;
        }
    },
    methods: {
        __,
    },
}
</script>
