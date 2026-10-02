<template>
  <section class="ns-box shadow-lg w-95vw md:w-[75vw] lg:w-[60vw] max-h-[85vh] flex flex-col">
    <header class="ns-box-header border-b p-3 flex items-center justify-between">
      <h2 class="font-bold text-fontcolor">{{ __('Pinned Products') }}</h2>
      <ns-close-button @click="closePopup()"></ns-close-button>
    </header>
    <div class="ns-box-body min-h-64 overflow-y-auto p-3">
      <div v-if="isLoading" class="min-h-56 flex flex-col items-center justify-center gap-2 text-fontcolor-soft">
        <ns-spinner size="24" border="4"></ns-spinner>
        <p>{{ __('Loading pinned products...') }}</p>
      </div>
      <div
        v-else-if="hasError"
        class="min-h-56 flex flex-col items-center justify-center gap-3 text-center text-fontcolor"
      >
        <i class="las la-exclamation-circle text-5xl text-error-secondary"></i>
        <p>{{ __('Pinned products could not be loaded.') }}</p>
        <ns-button type="info" @click="loadProducts()">{{ __('Retry') }}</ns-button>
      </div>
      <div
        v-else-if="products.length === 0"
        class="min-h-56 flex flex-col items-center justify-center gap-2 text-center text-fontcolor-soft"
      >
        <i class="las la-thumbtack text-5xl"></i>
        <p>{{ __('There are no pinned products to display.') }}</p>
      </div>
      <div v-else class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-3">
        <button
          v-for="product of products"
          :key="product.id"
          type="button"
          @click="addToCart(product)"
          class="min-h-24 overflow-hidden rounded border border-box-edge bg-box-background text-fontcolor hover:bg-box-elevation-hover focus:outline-none focus:ring-2 focus:ring-primary"
        >
          <span
            v-if="options.ns_pos_show_preview_pinned_products"
            class="h-28 w-full flex items-center justify-center overflow-hidden bg-box-elevation-background"
          >
            <img
              v-if="preview(product)"
              :src="preview(product).url"
              :alt="product.name"
              class="h-full w-full object-cover"
            />
            <i v-else class="las la-image text-5xl text-fontcolor-soft"></i>
          </span>
          <span class="flex min-h-16 flex-col items-center justify-center p-2">
            <strong class="text-sm text-center">{{ product.name }}</strong>
            <small v-if="product.unit_quantities?.length === 1" class="text-fontcolor-soft">{{
              price(product.unit_quantities[0])
            }}</small>
          </span>
        </button>
      </div>
    </div>
  </section>
</template>
<script lang="ts">
import popupCloser from '~/libraries/popup-closer';
import { nsHttpClient } from '~/bootstrap';
import { __ } from '~/libraries/lang';
import { nsCurrency } from '~/filters/currency';

export default {
  name: 'ns-pos-pinned-products-popup',
  props: ['popup'],
  data() {
    return { products: [], options: {}, isLoading: false, hasError: false, optionsSubscriber: null };
  },
  mounted() {
    this.popupCloser();
    this.optionsSubscriber = POS.options.subscribe((options) => (this.options = options));
    this.loadProducts();
  },
  unmounted() {
    this.optionsSubscriber?.unsubscribe();
  },
  methods: {
    __,
    popupCloser,
    closePopup() {
      this.popup.close();
    },
    loadProducts() {
      this.isLoading = true;
      this.hasError = false;
      nsHttpClient.get('/api/products/pos/pinned').subscribe({
        next: (result) => {
          this.products = result.pinnedProducts || [];
          this.isLoading = false;
        },
        error: () => {
          this.isLoading = false;
          this.hasError = true;
        }
      });
    },
    addToCart(product) {
      POS.addToCart(product);
    },
    preview(product) {
      return product.galleries?.find((gallery) => gallery.featured) || product.galleries?.[0] || null;
    },
    price(quantity) {
      if (this.options.ns_pos_vat === 'disabled') return nsCurrency(quantity.sale_price);
      return nsCurrency(
        this.options.ns_pos_prefered_price === 'gross_prices' ? quantity.sale_price_gross : quantity.sale_price_net
      );
    }
  }
};
</script>
