<template>
    <div class="ns-scale-settings-preview">
        <div class="border border-box-edge rounded p-4 bg-box-background">
            <h3 class="font-semibold mb-2 text-fontcolor">{{ __('Configuration Example') }}</h3>
            <div class="text-sm text-fontcolor-light whitespace-pre-wrap font-mono">{{ exampleText }}</div>
        </div>
    </div>
</template>

<script lang="ts">
import { __ } from '~/libraries/lang';

declare const nsHttpClient, ns, nsHooks;

export default {
    name: 'ns-scale-settings-preview',
    data() {
        return {
            prefix: '2',
            type: 'weight',
            productLength: 5,
            valueLength: 5,
        }
    },
    computed: {
        exampleText() {
            const example = [];
            
            // Format line
            example.push(`Format: ${this.prefix}${'X'.repeat(this.productLength)}${'W'.repeat(this.valueLength)}C`);
            example.push('');
            example.push('Where:');
            example.push(`- ${this.prefix} = Scale barcode prefix`);
            example.push(`- ${'X'.repeat(this.productLength)} = Product code (${this.productLength} digits)`);
            
            if (this.type === 'weight') {
                example.push(`- ${'W'.repeat(this.valueLength)} = Weight in grams (${this.valueLength} digits)`);
                example.push('- C = Check digit');
                example.push('');
                example.push('Example: 2123450012349');
                example.push('- Product code: 12345');
                example.push('- Weight: 00123 grams = 0.123 kg');
            } else {
                example.push(`- ${'W'.repeat(this.valueLength)} = Price in cents (${this.valueLength} digits)`);
                example.push('- C = Check digit');
                example.push('');
                example.push('Example: 2123450012349');
                example.push('- Product code: 12345');
                example.push('- Price: 00123 cents = $1.23');
            }
            
            return example.join('\n');
        }
    },
    mounted() {
        this.loadSettings();
        
        // Listen for settings saved event to reload the preview
        nsHooks.addAction('ns-settings-saved', 'ns-scale-settings-preview', () => {
            this.loadSettings();
        });
    },
    unmounted() {
        // Clean up the hook when component is destroyed
        nsHooks.removeAction('ns-settings-saved', 'ns-scale-settings-preview');
    },
    methods: {
        __,
        loadSettings() {
            // Fetch current settings from the API
            nsHttpClient.get('/api/settings/pos')
                .subscribe({
                    next: (response) => {
                        // Extract settings from response
                        if (response.tabs && response.tabs['scale-barcode']) {
                            const fields = response.tabs['scale-barcode'].fields;
                            
                            fields.forEach(field => {
                                if (field.name === 'ns_scale_barcode_prefix') {
                                    this.prefix = field.value || '2';
                                } else if (field.name === 'ns_scale_barcode_type') {
                                    this.type = field.value || 'weight';
                                } else if (field.name === 'ns_scale_barcode_product_length') {
                                    this.productLength = parseInt(field.value) || 5;
                                } else if (field.name === 'ns_scale_barcode_value_length') {
                                    this.valueLength = parseInt(field.value) || 5;
                                }
                            });
                        }
                    },
                    error: (error) => {
                        console.error('Failed to load scale barcode settings:', error);
                    }
                });
        }
    }
}
</script>

<style scoped>
.ns-scale-settings-preview {
    /* Component styles */
}
</style>
