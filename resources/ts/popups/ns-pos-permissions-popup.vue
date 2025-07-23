<template>
    <div class="bg-white rounded-lg shadow-xl w-[95vw] md:w-[60vw] lg:w-[40vw] w-full max-w-md mx-auto md:max-w-sm lg:max-w-md">
        <!-- Header -->
        <div class="flex items-center justify-between p-4 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-900">
                {{ __('Permission Required') }}
            </h3>
            <button 
                @click="close"
                class="text-gray-400 hover:text-gray-600 transition-colors"
            >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>

        <!-- Content -->
        <div class="p-6 text-center">
            <!-- Message -->
            <div class="mb-6">
                <div class="w-12 h-12 mx-auto mb-4 bg-yellow-100 rounded-full flex items-center justify-center">
                    <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                    </svg>
                </div>
                <h4 class="text-lg font-medium text-gray-900 mb-2">
                    {{ __('Temporary Permission Required') }}
                </h4>
                <p class="text-sm text-gray-600 mb-4">
                    {{ __('Access to this feature requires temporary permission from an administrator. Scan the QR code to proceed.' ) }}
                </p>
                <div class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                    {{ permissionData.name || __('Unknown Permission') }}
                </div>
            </div>

            <!-- QR Code Section -->
            <div class="mb-6">
                <div class="bg-gray-50 rounded-lg p-4 mb-4">
                    <div class="w-[240px] h-[240px] mx-auto bg-white rounded-lg p-2 shadow-inner flex items-center justify-center">
                        <!-- QR Code placeholder - you can replace this with actual QR code implementation -->
                        <div class="w-full h-full bg-gray-100 rounded flex items-center justify-center" ref="qrCodeContainer">
                            <canvas ref="qrCanvas" class="max-w-full max-h-full"></canvas>
                            <div v-if="!qrCodeGenerated" class="text-xs text-gray-500">
                                {{ __('Generating QR...') }}
                            </div>
                        </div>
                    </div>
                    <p class="text-xs text-gray-500 mt-2">
                        {{ __('Scan with administrator device') }}
                    </p>
                </div>
            </div>
            
        </div>
    </div>
</template>

<script lang="ts">
import { defineComponent, ref, onMounted, nextTick, onUnmounted } from 'vue'
import QRCode from 'qrcode'

declare const nsHttpClient, nsSnackBar, __, popupCloser, popupResolver;

export default defineComponent({
    name: 'NsPosPermissionsPopup',
    props: [ 'permission', 'popup', 'access_id', 'reject', 'resolve' ],
    emits: ['close', 'granted'],
    setup(props, { emit }) {
        const isLoading = ref(false)
        const qrCodeGenerated = ref(false)
        const qrCanvas = ref<HTMLCanvasElement>()
        const qrCodeData = ref('');
        const permissionData = ref({});

        const generateQRCode = async () => {
            return new Promise( async ( resolve, reject ) => {
                try {                
                    qrCodeData.value = JSON.stringify({
                        permission: props.permission,
                        access_id: props.access_id,
                    });
                    
                    if (qrCanvas.value) {
                        const result = await QRCode.toCanvas(qrCanvas.value, qrCodeData.value, {
                            width: 240,
                            margin: 1,
                            color: {
                                dark: '#1f2937',
                                light: '#ffffff'
                            }
                        })
                        qrCodeGenerated.value = true;

                        console.log({ result })

                        resolve(true);
                    }
                } catch (error) {
                    console.error('Failed to generate QR code:', error)
                    nsSnackBar.error(__('Failed to generate QR code'));
                    reject(error);
                }
            })
        }

        const close = () => {
            props.popup.close();
            props.reject( false );
        }

        
        const checkPermissionStatus = async () => {
            try {
                const result = await new Promise( ( resolve, reject ) => {
                    nsHttpClient.get(`/api/user/access/${props.access_id}`)
                        .subscribe({
                            next: (response) => {
                                if (response.status === 'granted') {
                                    nsHttpClient.get( `/api/user/access/${props.access_id}/use` )
                                        .subscribe({
                                            next: () => {
                                                resolve(true);
                                            },
                                            error: (error) => {
                                                reject( false );
                                            }
                                        });
                                } else {
                                    console.log('Permission not granted yet, will check again...');
                                }
                            },
                            error: (error) => {
                                reject( false );
                            }
                        });
                });
                
                props.popup.close();
                props.resolve( true );
            } catch( exception ) {
                console.error('Failed to check permission status:', exception);
                close();
            }
        }

        onUnmounted(() => {
            if (pollInterval) {
                console.log('Clearing permission status polling interval');
                clearInterval(pollInterval);
            }
        });

        let pollInterval: number | null = null

        onMounted(async () => {
            await nextTick()
            await generateQRCode()

            // Poll for permission status (for QR code scanning)
            pollInterval = window.setInterval(checkPermissionStatus, 2000)

            nsHttpClient.get( `/api/permissions/${props.permission}` )
                .subscribe({
                    next: permission => {
                        permissionData.value = permission;
                    },
                    error: error => {
                        close();
                        nsSnackBar.error(__('Failed to load permission data'));
                    }
                })
        })

        return {
            isLoading,
            qrCodeGenerated,
            permissionData,
            qrCanvas,
            close,
            onUnmounted,
            __,
            popupCloser,
            popupResolver
        }
    }
});
</script>

<style scoped>
@media (max-width: 640px) {
    .fixed > div {
        width: 95vw;
        max-width: none;
    }
}
</style>
