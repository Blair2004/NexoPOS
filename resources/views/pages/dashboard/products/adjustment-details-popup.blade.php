<script>
document.addEventListener( 'DOMContentLoaded', () => {
    const _i18nAdjustmentDetails = "{{ __( 'Adjustment Details' ) }}";
    const _i18nLoading           = "{{ __( 'Loading...' ) }}";
    const _i18nReference         = "{{ __( 'Reference' ) }}";
    const _i18nProduct           = "{{ __( 'Product' ) }}";
    const _i18nUnit              = "{{ __( 'Unit' ) }}";
    const _i18nAction            = "{{ __( 'Action' ) }}";
    const _i18nQuantity          = "{{ __( 'Quantity' ) }}";
    const _i18nDescription       = "{{ __( 'Description' ) }}";
    const _i18nNoItems           = "{{ __( 'No items found.' ) }}";
    const _i18nClose             = "{{ __( 'Close' ) }}";
    const _i18nAdd               = "{{ __( 'Add' ) }}";
    const _i18nRemove            = "{{ __( 'Remove' ) }}";
    const _i18nSet               = "{{ __( 'Set' ) }}";
    const _i18nError             = "{{ __( 'Unable to load adjustment details.' ) }}";

    const NsAdjustmentDetailsPopup = defineComponent({
        name: 'NsAdjustmentDetailsPopup',
        props: [ 'popup' ],
        data() {
            return {
                adjustment: null,
                loading: true,
                errorMessage: null,
                i18n: {
                    adjustmentDetails : _i18nAdjustmentDetails,
                    loading           : _i18nLoading,
                    reference         : _i18nReference,
                    product           : _i18nProduct,
                    unit              : _i18nUnit,
                    action            : _i18nAction,
                    quantity          : _i18nQuantity,
                    description       : _i18nDescription,
                    noItems           : _i18nNoItems,
                    close             : _i18nClose,
                    add               : _i18nAdd,
                    remove            : _i18nRemove,
                    set               : _i18nSet,
                    error             : _i18nError,
                },
            };
        },
        mounted() {
            this.popupCloser();
            const id = this.popup.params.adjustmentId;
            nsHttpClient.get( `/api/products/adjustments/${id}` ).subscribe({
                next: ( response ) => {
                    this.adjustment = response;
                    this.loading = false;
                },
                error: ( err ) => {
                    this.errorMessage = ( err && err.message ) ? err.message : this.i18n.error;
                    this.loading = false;
                }
            });
        },
        computed: {
            headerTitle() {
                if ( this.loading ) {
                    return this.i18n.loading;
                }
                if ( this.adjustment && this.adjustment.title ) {
                    return this.adjustment.title;
                }
                return this.i18n.adjustmentDetails;
            }
        },
        methods: {
            popupCloser,
            close() {
                this.popup.close();
            },
            getActionLabel( action ) {
                const map = {
                    'add'    : this.i18n.add,
                    'remove' : this.i18n.remove,
                    'set'    : this.i18n.set,
                };
                return map[ action ] || action;
            },
            getActionClass( action ) {
                const map = {
                    'add'    : 'bg-success-primary text-success-tertiary border border-success-secondary',
                    'remove' : 'bg-error-primary text-error-tertiary border border-error-secondary',
                    'set'    : 'bg-info-primary text-info-tertiary border border-info-secondary',
                };
                return map[ action ] || '';
            }
        },
        template: `
            <div class="shadow-lg w-[85.71vw] md:w-[71.43vw] lg:w-[57.14vw] ns-box flex flex-col" style="max-height:90vh;">
                <div class="p-3 border-b border-box-edge ns-box-header flex items-center justify-between">
                    <div>
                        <h3 class="text-fontcolor-hard font-semibold text-lg">@{{ headerTitle }}</h3>
                        <p class="text-fontcolor-soft text-sm" v-if="adjustment && adjustment.code">@{{ i18n.reference }}: @{{ adjustment.code }}</p>
                    </div>
                    <ns-close-button @click="close()"></ns-close-button>
                </div>
                <div class="p-4 ns-box-body flex-auto overflow-y-auto">
                    <div v-if="loading" class="flex justify-center py-8">
                        <ns-spinner></ns-spinner>
                    </div>
                    <div v-else-if="errorMessage" class="bg-error-primary border border-error-secondary text-error-tertiary p-3 rounded">
                        @{{ errorMessage }}
                    </div>
                    <template v-else-if="adjustment">
                        <table class="w-full text-sm">
                            <thead class="bg-table-th border-b border-table-th-edge">
                                <tr>
                                    <th class="text-left px-3 py-2 text-fontcolor-hard font-semibold">@{{ i18n.product }}</th>
                                    <th class="text-left px-3 py-2 text-fontcolor-hard font-semibold">@{{ i18n.unit }}</th>
                                    <th class="text-center px-3 py-2 text-fontcolor-hard font-semibold">@{{ i18n.action }}</th>
                                    <th class="text-right px-3 py-2 text-fontcolor-hard font-semibold">@{{ i18n.quantity }}</th>
                                    <th class="text-left px-3 py-2 text-fontcolor-hard font-semibold">@{{ i18n.description }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="item in adjustment.items" :key="item.id" class="border-b border-box-edge">
                                    <td class="px-3 py-2 text-fontcolor">@{{ item.product_name }}</td>
                                    <td class="px-3 py-2 text-fontcolor">@{{ item.unit_name }}</td>
                                    <td class="px-3 py-2 text-center">
                                        <span class="px-2 py-0.5 rounded text-xs font-medium" :class="getActionClass( item.adjust_action )">
                                            @{{ getActionLabel( item.adjust_action ) }}
                                        </span>
                                    </td>
                                    <td class="px-3 py-2 text-right text-fontcolor font-mono">@{{ item.quantity }}</td>
                                    <td class="px-3 py-2 text-fontcolor-soft">@{{ item.description || '\u2014' }}</td>
                                </tr>
                                <tr v-if="! adjustment.items || adjustment.items.length === 0">
                                    <td colspan="5" class="px-3 py-4 text-center text-fontcolor-soft">@{{ i18n.noItems }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </template>
                </div>
                <div class="p-3 border-t border-box-edge ns-box-footer flex justify-end">
                    <ns-button type="info" @click="close()">@{{ i18n.close }}</ns-button>
                </div>
            </div>
        `
    });

    nsEvent.subject().subscribe( event => {
        if ( event.identifier === 'ns-table-row-action' && event.value.action.identifier === 'view-adjustment-details' ) {
            Popup.show( NsAdjustmentDetailsPopup, { adjustmentId: event.value.row.id });
        }
    });
});
</script>
