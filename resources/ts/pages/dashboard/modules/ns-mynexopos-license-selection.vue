<template>
    <div class="ns-box shadow-xl rounded overflow-hidden w-[95vw] md:w-[72vw] lg:w-[54vw] xl:w-[42vw] max-h-[92vh] flex flex-col border border-box-edge bg-box-background text-fontcolor">
        <div class="relative border-b border-box-edge bg-box-elevation-background">
            <div v-if="productThumbnail" class="absolute inset-0 opacity-10">
                <img :src="productThumbnail" alt="" class="w-full h-full object-cover">
            </div>
            <div class="relative px-5 py-5 flex items-start justify-between gap-4">
                <div class="flex items-center gap-4 min-w-0">
                    <div class="h-14 w-14 shrink-0 rounded border border-box-edge bg-box-background flex items-center justify-center overflow-hidden">
                        <img v-if="productIcon" :src="productIcon" alt="" class="w-full h-full object-cover">
                        <i v-else class="las la-cube text-3xl text-info-tertiary"></i>
                    </div>
                    <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-2">
                            <h1 class="text-xl font-bold text-fontcolor-hard leading-tight truncate">{{ productName }}</h1>
                            <span v-if="productVersion" class="text-xs px-2 py-1 rounded border border-box-edge bg-box-background text-fontcolor-soft">
                                v{{ productVersion }}
                            </span>
                        </div>
                        <p class="text-sm text-fontcolor-soft mt-1">{{ __( 'Select a license' ) }}</p>
                    </div>
                </div>
                <ns-close-button v-if="popup" @click="close()"></ns-close-button>
            </div>
        </div>

        <div class="p-5 overflow-y-auto">
            <div v-if="normalizedLicenses.length > 0" class="grid grid-cols-1 gap-3">
                <button
                    v-for="license in normalizedLicenses"
                    :key="license.id || license.license_uuid"
                    type="button"
                    class="w-full text-left rounded border p-4 bg-box-elevation-background transition-colors"
                    :class="licenseCardClass( license )"
                    @click="selectLicense( license )">
                    <div class="flex items-start justify-between gap-4">
                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="font-semibold text-fontcolor-hard">{{ license.product_name || productName }}</span>
                                <span class="text-xs px-2 py-1 rounded border" :class="statusClass( license )">
                                    {{ statusLabel( license ) }}
                                </span>
                                <span v-if="license.is_gifted" class="text-xs px-2 py-1 rounded border border-success-secondary bg-success-primary text-success-tertiary">
                                    {{ __( 'Gifted' ) }}
                                </span>
                            </div>
                            <div class="mt-3 grid grid-cols-1 md:grid-cols-3 gap-3 text-sm">
                                <div>
                                    <span class="block text-xs uppercase text-fontcolor-soft">{{ __( 'License' ) }}</span>
                                    <span class="block mt-1 font-mono text-xs text-fontcolor-hard truncate">{{ license.license_uuid }}</span>
                                </div>
                                <div>
                                    <span class="block text-xs uppercase text-fontcolor-soft">{{ __( 'Expires' ) }}</span>
                                    <span class="block mt-1 text-fontcolor-hard">{{ formatDate( license.license_expiration ) }}</span>
                                </div>
                                <div>
                                    <span class="block text-xs uppercase text-fontcolor-soft">{{ __( 'Seats' ) }}</span>
                                    <span class="block mt-1 text-fontcolor-hard">{{ license.quantity || 1 }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="h-9 w-9 shrink-0 rounded-full border flex items-center justify-center" :class="isSelected( license ) ? 'border-info-secondary bg-info-primary text-info-tertiary' : 'border-box-edge bg-box-background text-fontcolor-soft'">
                            <i :class="isSelected( license ) ? 'las la-check' : 'las la-circle'"></i>
                        </div>
                    </div>
                </button>
            </div>

            <div v-else class="border border-box-edge rounded bg-box-elevation-background px-5 py-8 text-center">
                <div class="mx-auto h-14 w-14 rounded-full border border-warning-secondary bg-warning-primary text-warning-tertiary flex items-center justify-center">
                    <i class="las la-key text-3xl"></i>
                </div>
                <h2 class="mt-4 text-lg font-semibold text-fontcolor-hard">{{ __( 'No license available' ) }}</h2>
                <p class="mt-2 text-sm text-fontcolor-soft">{{ __( 'This module does not have an assignable license for this account.' ) }}</p>
            </div>
        </div>

        <div class="border-t border-box-edge bg-box-elevation-background p-4 flex flex-col-reverse md:flex-row md:items-center md:justify-between gap-3">
            <div class="text-xs text-fontcolor-soft flex items-center gap-2 min-w-0">
                <i class="las la-shield-alt shrink-0"></i>
                <span class="truncate">{{ selectedLicense ? selectedLicense.license_uuid : __( 'No license selected' ) }}</span>
            </div>
            <div class="flex items-center justify-end gap-2">
                <ns-button v-if="popup" @click="close()">{{ __( 'Cancel' ) }}</ns-button>
                <ns-button type="info" :disabled="! canContinue" @click="continueWithLicense()">
                    <span class="flex items-center justify-center gap-2">
                        <i class="las la-download"></i>
                        <span>{{ __( 'Continue' ) }}</span>
                    </span>
                </ns-button>
            </div>
        </div>
    </div>
</template>
<script lang="ts">
import popupCloser from '~/libraries/popup-closer';
import popupResolver from '~/libraries/popup-resolver';

declare const __;

export default {
    props: [ 'item', 'licenses', 'popup' ],
    data() {
        return {
            selectedLicense: null,
        }
    },
    computed: {
        normalizedLicenses() {
            if ( Array.isArray( this.licenses ) ) {
                return this.licenses;
            }

            if ( Array.isArray( this.licenses?.data ) ) {
                return this.licenses.data;
            }

            return [];
        },

        product() {
            return this.item?.product || this.normalizedLicenses[0]?.product || {};
        },

        productName() {
            return this.item?.name || this.item?.product_name || this.normalizedLicenses[0]?.product_name || __( 'Module' );
        },

        productVersion() {
            return this.item?.version || this.item?.product_version || this.normalizedLicenses[0]?.product_version || '';
        },

        productIcon() {
            return this.item?.icon || this.product?.icon || this.normalizedLicenses[0]?.product?.icon || '';
        },

        productThumbnail() {
            return this.item?.thumbnail || this.product?.thumbnail || this.normalizedLicenses[0]?.product?.thumbnail || '';
        },

        canContinue() {
            return this.selectedLicense && ! this.selectedLicense.expired && this.selectedLicense.license_status === 'active';
        }
    },
    mounted() {
        this.selectedLicense = this.normalizedLicenses.find( license => {
            return license.license_status === 'active' && ! license.expired;
        }) || this.normalizedLicenses[0] || null;

        this.popupCloser();
    },
    methods: {
        __,
        popupCloser,
        popupResolver,

        selectLicense( license ) {
            this.selectedLicense = license;
        },

        isSelected( license ) {
            return this.selectedLicense && ( this.selectedLicense.id === license.id || this.selectedLicense.license_uuid === license.license_uuid );
        },

        licenseCardClass( license ) {
            if ( this.isSelected( license ) ) {
                return 'border-info-secondary bg-info-primary';
            }

            if ( license.expired || license.license_status !== 'active' ) {
                return 'border-box-edge opacity-75 hover:bg-box-background';
            }

            return 'border-box-edge hover:border-info-secondary hover:bg-box-background';
        },

        statusClass( license ) {
            if ( license.expired ) {
                return 'border-error-secondary bg-error-primary text-error-tertiary';
            }

            if ( license.license_status === 'active' ) {
                return 'border-success-secondary bg-success-primary text-success-tertiary';
            }

            return 'border-warning-secondary bg-warning-primary text-warning-tertiary';
        },

        statusLabel( license ) {
            if ( license.expired ) {
                return __( 'Expired' );
            }

            return __( license.license_status || 'Unknown' );
        },

        formatDate( value ) {
            if ( ! value ) {
                return __( 'Never' );
            }

            const date = new Date( value );

            if ( Number.isNaN( date.getTime() ) ) {
                return value;
            }

            return new Intl.DateTimeFormat( undefined, {
                year: 'numeric',
                month: 'short',
                day: 'numeric',
            }).format( date );
        },

        close() {
            if ( this.popup ) {
                this.popupResolver( false );
            }
        },

        continueWithLicense() {
            if ( ! this.canContinue ) {
                return;
            }

            this.$emit( 'select', this.selectedLicense );

            if ( this.popup?.params?.resolve ) {
                this.popup.params.resolve( this.selectedLicense );
            }

            this.close();
        }
    }
}
</script>
