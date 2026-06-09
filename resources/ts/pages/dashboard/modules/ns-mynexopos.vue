<template>
    <div class="ns-box shadow-xl rounded overflow-hidden w-[85.71vw] md:w-[57.14vw] lg:w-[42.86vw] flex flex-col border border-box-edge bg-box-background text-fontcolor">
        <div class="border-b border-box-edge px-6 py-5 bg-box-elevation-background">
            <div class="flex items-center gap-4">
                <div class="h-12 w-12 shrink-0 rounded border border-info-secondary bg-info-primary text-info-tertiary flex items-center justify-center">
                    <i class="las la-shield-alt text-2xl"></i>
                </div>
                <div class="min-w-0">
                    <h1 class="text-2xl font-bold text-fontcolor-hard leading-tight">{{ __( 'My NexoPOS' ) }}</h1>
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
                    <h2 class="font-semibold text-fontcolor-hard">{{ __( 'Connect your marketplace account' ) }}</h2>
                    <p class="text-sm text-fontcolor-soft leading-6">
                        {{ __( 'A new browser window will open so you can sign in securely, then return here to continue installing modules.' ) }}
                    </p>
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