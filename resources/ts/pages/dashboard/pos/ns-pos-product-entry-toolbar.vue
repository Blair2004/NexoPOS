<template>
	<div :class="embedded ? 'flex-auto min-w-64 border-0 rounded-none' : 'border rounded'"
		class="flex overflow-hidden bg-input-background border-input-edge text-fontcolor">
		<div class="ns-button">
			<button :title="__('Search for products.')" @click="openSearchPopup()"
				class="w-10 h-10 border-r border-input-edge outline-hidden hover:bg-input-button-hover">
				<i class="las la-search"></i>
			</button>
		</div>
		<div>
			<button :title="__('Toggle merging similar products.')" @click="posToggleMerge()"
				:class="settings.ns_pos_items_merge ? 'bg-input-button-active' : ''"
				class="outline-hidden w-10 h-10 bg-box-background border-input-edge border-r hover:bg-input-button-hover">
                        <i class="las la-compress-arrows-alt"></i>
			</button>
		</div>
		<template v-if="options.ns_pos_barcode_reader_type === 'wireless'">
			<div v-if="!settings.marketplace_connected" class="ns-button border-r border-input-edge text-error-secondary">
				<button :title="__('Connect/Disconnect wireless barcode reader.')"
					@click="inviteToMyNexoPOSConnexion()"
					class="outline-hidden">
					<span class="px-2 h-10 flex items-center justify-center"><i
							class="las la-exclamation-triangle text-lg"></i></span>
				</button>
			</div>
			<ns-pos-grid-wireless-barcode v-else></ns-pos-grid-wireless-barcode>
		</template>
		<div v-else>
			<button :title="__('Toggle auto focus.')"
				@click="options.ns_pos_force_autofocus = !options.ns_pos_force_autofocus"
				:class="options.ns_pos_force_autofocus ? 'bg-input-button-active' : ''"
				class="outline-hidden w-10 h-10 border-r bg-box-background border-input-edge hover:bg-input-button-hover">
				<i class="las la-barcode"></i>
			</button>
		</div>
		<input ref="search" v-model="barcode" type="text" :aria-label="__('Barcode')"
			class="min-w-0 flex-auto bg-input-background text-fontcolor outline-hidden px-2" />
		<div class="ns-button">
			<button v-if="showPinnedProducts" type="button" @click="openPinnedProducts()"
				class="shrink-0 border-l px-3 h-10 font-semibold">
				<i class="las la-thumbtack mr-1"></i>{{ __('Pinned Products') }}
			</button>
		</div>
	</div>
</template>
<script lang="ts">
import { nsHttpClient, nsSnackBar } from '../../../bootstrap';
import { __ } from '~/libraries/lang';
import nsPosSearchProductVue from '~/popups/ns-pos-search-product.vue';
import nsPosPinnedProductsPopup from '~/popups/ns-pos-pinned-products-popup.vue';
import NsPosGridWirelessBarcode from './ns-pos-grid-wireless-barcode.vue';
import { ProductUnitPromise } from './queues/products/product-unit';

declare const nsNotice;

export default {
	name: 'ns-pos-product-entry-toolbar',
	props: {
		embedded: { type: Boolean, default: false },
		showPinnedProducts: { type: Boolean, default: false }
	},
	components: { 'ns-pos-grid-wireless-barcode': NsPosGridWirelessBarcode },
	data() {
		return {
			barcode: '',
			options: {},
			settings: {},
			searchTimeout: null,
			interval: null,
			optionsSubscriber: null,
			settingsSubscriber: null,
			wirelessStateSubscriber: null
		};
	},
	watch: {
		barcode() {
			if (this.options.ns_pos_force_autofocus) {
				clearTimeout(this.searchTimeout);
				this.searchTimeout = setTimeout(() => this.submitSearch(this.barcode), 200);
			}
		}
	},
	mounted() {
		this.settingsSubscriber = POS.settings.subscribe((settings) => {
			this.settings = settings;
		});
		this.optionsSubscriber = POS.options.subscribe((options) => (this.options = options));
		this.wirelessStateSubscriber = POS.wirelessBarcodeState.property('barcode').subscribe((state) => {
			if (typeof state === 'string' && state.length > 0) this.submitSearch(state);
		});
		this.interval = setInterval(() => this.checkFocus(), 500);
		nsHooks.addAction('ns-after-cart-changed', 'ns-pos-product-entry-toolbar', () => {
			POS.wirelessBarcodeState.update({ barcode: '' });
		});
		for (const shortcut in nsShortcuts) {
			if (shortcut === 'ns_pos_keyboard_quick_search') {
				nsHotPress
					.create('search-popup')
					.whenNotVisible(['.is-popup', '#product-search'])
					.whenPressed(nsShortcuts[shortcut] !== null ? nsShortcuts[shortcut].join('+') : null, (event) => {
						event.preventDefault();
						this.openSearchPopup();
					});
			}
			if (shortcut === 'ns_pos_keyboard_toggle_merge') {
				nsHotPress
					.create('toggle-merge')
					.whenNotVisible(['.is-popup'])
					.whenPressed(nsShortcuts[shortcut] !== null ? nsShortcuts[shortcut].join('+') : null, (event) => {
						event.preventDefault();
						this.posToggleMerge();
					});
			}
		}
	},
	unmounted() {
		this.settingsSubscriber?.unsubscribe();
		this.optionsSubscriber?.unsubscribe();
		this.wirelessStateSubscriber?.unsubscribe();
		clearInterval(this.interval);
		clearTimeout(this.searchTimeout);
		nsHooks.removeAction('ns-after-cart-changed', 'ns-pos-product-entry-toolbar');
		nsHotPress.destroy('search-popup');
		nsHotPress.destroy('toggle-merge');
	},
	methods: {
		__,
		openSearchPopup() {
			Popup.show(nsPosSearchProductVue);
		},
		openPinnedProducts() {
			Popup.show(nsPosPinnedProductsPopup);
		},
		posToggleMerge() {
			POS.set('ns_pos_items_merge', !this.settings.ns_pos_items_merge);
		},
		checkFocus() {
			if (this.options.ns_pos_force_autofocus && document.querySelectorAll('.is-popup').length === 0)
				this.$refs.search?.focus();
		},
		async resolveBarcodeProduct(result) {
			if (result.unit === null && result.product.id) {
				const unitResult: any = await new ProductUnitPromise({ $original: () => result.product }).run();
				result.unitQuantity = result.product.unit_quantities.find(
					(quantity) => quantity.id === unitResult.unit_quantity_id
				);
				result.unit = result.unitQuantity.unit;
			}
			const product: any = {
				name: result.product.name,
				id: result.product.id,
				product_type: result.product.product_type,
				rate: result.product.rate,
				tax_group_id: result.product.tax_group_id,
				tax_type: result.product.tax_type,
				unit_id: result.unit.id,
				unit_price: result.unitQuantity.sale_price,
				price_gross: result.unitQuantity.sale_price_gross,
				price_net: result.unitQuantity.sale_price_net,
				unit_name: result.unit.name,
				unitQuantity: result.unitQuantity
			};
			if (result.scale?.type === 'weight') product.quantity = result.scale.value;
			if (result.scale?.type === 'price') {
				const unitPrice =
					result.product.selectedUnitQuantity?.sale_price || result.product.unit_quantities[0]?.sale_price || 0;
				if (unitPrice > 0) product.quantity = result.scale.value / unitPrice;
			}
			return product;
		},
		submitSearch(value) {
			if (!value.length) return;
			const url = nsHooks.applyFilters(
				'ns-pos-submit-search-url',
				`/api/products/search/using-barcode/${value}`,
				value
			);
			nsHttpClient.get(url).subscribe({
				next: async (result) => {
					this.barcode = '';
					try {
						POS.addToCart(await this.resolveBarcodeProduct(result));
					} catch (error) {
						nsSnackBar.error(__('An unexpected error occurred while fetching the unit quantity for this product.'));
					}
				},
				error: (error) => {
					this.barcode = '';
					nsSnackBar.error(error.message);
				}
			});
		},
		inviteToMyNexoPOSConnexion() {
			nsNotice.info(
				__('Authentication Required'),
				__('You need to connect your installation to My NexoPOS for using websocket features.'),
				{
					actions: {
						close: { type: 'info', label: __('No thanks') },
						confirm: {
							label: __('Continue'),
							type: 'error',
							onClick: () => {
								document.location = POS.settings.getValue().urls.marketplace_url;
							}
						}
					}
				}
			);
		}
	}
};
</script>
