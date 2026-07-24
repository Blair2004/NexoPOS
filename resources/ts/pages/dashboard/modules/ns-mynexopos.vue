<template>
    <div class="ns-box shadow-xl rounded overflow-hidden w-[85.71vw] md:w-[57.14vw] lg:w-[42.86vw] flex flex-col border border-box-edge bg-box-background text-fontcolor">
        <div class="border-b border-box-edge px-6 py-5 bg-box-elevation-background">
            <div class="flex items-center gap-4">
                <div class="h-12 w-12 shrink-0 rounded border border-info-secondary bg-info-primary text-info-tertiary flex items-center justify-center">
                    <i class="las la-shield-alt text-2xl"></i>
                </div>
                <div class="min-w-0">
                    <h1 class="text-2xl font-bold text-fontcolor leading-tight">{{ __( 'My NexoPOS' ) }}</h1>
                    <p class="text-sm text-fontcolor-soft mt-1">{{ __( 'Authenticate to install modules' ) }}</p>
                </div>
            </div>
        </div>

        <div class="px-6 py-6">
            <div class="border border-box-edge rounded bg-box-elevation-background px-4 py-3 flex items-start gap-3">
                <div class="mt-0.5 text-info-tertiary">
                    <i class="las la-store-alt text-xl"></i>
                </div>
                <div class="space-y-1">
                    <h2 class="font-semibold text-fontcolor">{{ __( 'Connect your marketplace account' ) }}</h2>
                    <p class="text-sm text-fontcolor-soft leading-6">
                        {{ __( 'A new browser window will open so you can sign in securely, then return here to continue installing modules.' ) }}
                    </p>
                </div>
            </div>

            <div class="mt-5 rounded-lg border border-box-edge bg-background p-4">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <h3 class="text-sm font-semibold text-fontcolor">{{ __( 'Why connect your account?' ) }}</h3>
                        <p class="mt-1 text-xs text-fontcolor-soft">{{ __( 'Unlock marketplace access and keep module installation smooth.' ) }}</p>
                    </div>
                    <div class="rounded-full bg-primary/10 px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.16em] text-primary">
                        {{ __( 'Benefits' ) }}
                    </div>
                </div>

                <div class="mt-4 grid gap-3">
                    <div class="flex items-start gap-3 rounded-2xl bg-box-elevation-background px-3 py-3">
                        <span class="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-success/10 text-success">
                            <i class="las la-check text-lg"></i>
                        </span>
                        <div>
                            <h4 class="text-sm font-medium text-fontcolor">{{ __( 'One-click module installation' ) }}</h4>
                            <p class="mt-1 text-xs leading-5 text-fontcolor-soft">
                                {{ __( 'Authenticate once and keep moving without repeating setup steps for every extension.' ) }}
                            </p>
                        </div>
                    </div>

                    <div class="flex items-start gap-3 rounded-2xl bg-box-elevation-background px-3 py-3">
                        <span class="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-info/10 text-info">
                            <i class="las la-plug text-lg"></i>
                        </span>
                        <div>
                            <h4 class="text-sm font-medium text-fontcolor">{{ __( 'Socket Features' ) }}</h4>
                            <p class="mt-1 text-xs leading-5 text-fontcolor-soft">
                                {{ __( 'These are useful to use the Barcode Scanner Utility on NexoPOS.' ) }}
                            </p>
                        </div>
                    </div>

                    <div class="flex items-start gap-3 rounded-2xl bg-box-elevation-background px-3 py-3">
                        <span class="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-warning/10 text-warning">
                            <i class="las la-bolt text-lg"></i>
                        </span>
                        <div>
                            <h4 class="text-sm font-medium text-fontcolor">{{ __( 'Faster updates and upgrades' ) }}</h4>
                            <p class="mt-1 text-xs leading-5 text-fontcolor-soft">
                                {{ __( 'Get access to the latest extensions, fixes, and improvements as they ship.' ) }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="mt-5 flex flex-col-reverse md:flex-row md:items-center md:justify-between gap-3">
                <div class="text-xs text-fontcolor-soft flex items-center gap-2">
                    <i class="las la-lock"></i>
                    <span>{{ __( 'Your credentials are handled by My NexoPOS.' ) }}</span>
                </div>
                <ns-button
                    :href="authorizationUrl"
                    target="_blank"
                    type="info"
                    :disabled="isOpening"
                    @click="handleAuthenticationClick"
                    class="min-w-[190px]">
                    <span class="flex items-center justify-center gap-2">
                        <i v-if="isOpening" class="las la-sync animate-spin"></i>
                        <i v-else class="las la-external-link-alt"></i>
                        <span>{{ isOpening ? __( 'Opening My NexoPOS...' ) : __( 'Go to My NexoPOS' ) }}</span>
                    </span>
                </ns-button>
            </div>
        </div>
    </div>
</template>
<script lang="ts">
declare const __;
declare const authorizationUrl;

export default {
    data() {
        return {
            isOpening: false,
            authorizationUrl,
        }
    },
    methods: {
        __,
        handleAuthenticationClick() {
            this.isOpening = true;

            setTimeout( () => {
                this.isOpening = false;
            }, 3000 );
        }
    }
}
</script>