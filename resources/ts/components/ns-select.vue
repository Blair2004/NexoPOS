<template>
    <div class="flex flex-col flex-auto ns-select" :class="hasError ? 'has-error' : 'is-pristine'" >
        <label :for="field.name" class="block leading-5 font-medium"><slot></slot></label>
        <div class="border mt-1 relative rounded-md shadow-sm mb-1 overflow-hidden">
            <select 
                :disabled="field.disabled ? field.disabled : false" 
                :name="field.name" v-model="field.value" 
                :class="inputClass" 
                class="form-input block w-full pl-7 pr-12 sm:text-sm sm:leading-5 h-10 appearance-none">
                <option :value="null">{{ __( 'Choose an option' ) }}</option>
                <option 
                    v-for="(flatOption, index) in flattenedOptions" 
                    :key="index"
                    :value="flatOption.value"
                    :disabled="flatOption.disabled"
                    class="py-2">
                    {{ flatOption.label }}
                </option>
            </select>
        </div>
        <ns-field-description :field="field"></ns-field-description>
    </div>
</template>
<script>
import { __ } from '~/libraries/lang';

export default {
    data: () => {
        return {
        }
    },
    props: [ 'name', 'placeholder', 'field', 'leading' ],
    computed: {
        hasError() {
            if ( this.field.errors !== undefined && this.field.errors.length > 0 ) {
                return true;
            }
            return false;
        },
        disabledClass() {
            return this.field.disabled ? 'ns-disabled cursor-not-allowed' : '';
        },
        inputClass() {
            return this.disabledClass + ' ' + this.leadClass
        },
        leadClass() {
            return this.leading ? 'pl-8' : 'px-4';
        },
        flattenedOptions() {
            return this.flattenOptions(this.field.options || [], 0, []);
        }
    },
    mounted() {
        // ...
    },
    methods: { 
        __,
        /**
         * Recursively flatten hierarchical options into a flat list
         * with visual hierarchy using box-drawing characters
         * 
         * @param {Array} options - Array of option objects
         * @param {Number} level - Current nesting level
         * @param {Array} isLast - Array tracking if parent levels are last items
         * @returns {Array} Flattened options with visual hierarchy
         */
        flattenOptions(options, level = 0, isLast = []) {
            const flattened = [];
            
            options.forEach((option, index) => {
                const isLastItem = index === options.length - 1;
                const hasChildren = Array.isArray(option.value);
                
                // Build the tree prefix using box-drawing characters
                let prefix = '';
                
                // Add vertical lines for parent levels
                for (let i = 0; i < level; i++) {
                    if (i === level - 1) {
                        // Current level: use corner or T-junction
                        prefix += isLastItem ? '└─ ' : '├─ ';
                    } else {
                        // Parent levels: show vertical line if not last
                        prefix += isLast[i] ? '\u00A0\u00A0\u00A0\u00A0' : '│\u00A0\u00A0';
                    }
                }
                
                // Determine if this option should be disabled
                // By default, parent nodes (hasChildren) are disabled unless explicitly set
                const isDisabled = option.disabled !== undefined 
                    ? option.disabled 
                    : hasChildren; // Parent nodes are disabled by default
                
                if (hasChildren) {
                    // This is a parent node - add it as disabled
                    flattened.push({
                        label: prefix + option.label,
                        value: option.value, // Keep original value (array) for reference
                        disabled: isDisabled,
                        level: level,
                        isParent: true
                    });
                    
                    // Recursively process children
                    const childIsLast = [...isLast, isLastItem];
                    const children = this.flattenOptions(option.value, level + 1, childIsLast);
                    flattened.push(...children);
                } else {
                    // This is a leaf node - add it normally
                    flattened.push({
                        label: prefix + option.label,
                        value: option.value,
                        disabled: isDisabled,
                        level: level,
                        isParent: false
                    });
                }
            });
            
            return flattened;
        }
    },
}
</script>