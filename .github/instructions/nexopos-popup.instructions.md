---
applyTo: '**'
---

# NexoPOS Popup API Guide

This comprehensive guide explains how to use the NexoPOS Popup system for displaying modal dialogs, forms, and interactive components throughout the application.

## Overview

The Popup API in NexoPOS provides a centralized, Promise-based system for displaying modal popups. It's built on top of Vue.js and RxJS, offering reactive state management and elegant popup lifecycle control.

**Location**: `resources/ts/libraries/popup.ts`

## Core Concepts

### Popup Class

The main `Popup` class handles all popup operations:

```typescript
import { Popup } from '~/libraries/popup';

// Static method to show popups
Popup.show(component, params, config);
```

### Popup Structure

Every popup has:
- **Component**: The Vue component to render
- **Params**: Data/configuration passed to the component
- **Config**: Display and behavior options
- **Hash**: Unique identifier for the popup instance
- **Close**: Method to close the popup

## Basic Usage

### 1. Simple Popup

```typescript
import { Popup } from '~/libraries/popup';
import MyPopupComponent from '~/popups/my-popup.vue';

// Show a simple popup
Popup.show(MyPopupComponent, {
    title: 'My Title',
    message: 'My message content'
});
```

### 2. Promise-Based Popup

Most popups use Promises to handle user actions:

```typescript
const popup = new Promise((resolve, reject) => {
    Popup.show(MyPopupComponent, {
        resolve,
        reject,
        data: someData
    });
});

popup.then(result => {
    // Handle success/confirmation
    console.log('User confirmed:', result);
}).catch(error => {
    // Handle cancellation/error
    console.log('User cancelled');
});
```

### 3. Async/Await Pattern

```typescript
try {
    const result = await new Promise((resolve, reject) => {
        Popup.show(MyPopupComponent, {
            resolve,
            reject,
            someData: 'value'
        });
    });
    
    // Process result
    console.log('Result:', result);
} catch (error) {
    // Handle cancellation
    console.log('Cancelled or error');
}
```

## Built-in Popup Components

NexoPOS includes several pre-built popup components for common use cases.

**Important Note for Modules:** All built-in popup components (nsAlertPopup, nsConfirmPopup, nsPromptPopup, nsSelectPopup, nsMedia, nsPosLoadingPopup) are globally available on the `window` context. **Modules should NOT import these components** - they can be used directly by name. The import statements shown below are only for core application code.

### 1. Alert Popup (nsAlertPopup)

Display simple information messages.

```typescript
// Core application code (with import)
import nsAlertPopup from '~/popups/ns-alert-popup.vue';

Popup.show(nsAlertPopup, {
    title: 'Information',
    message: 'This is an informational message',
    size: 'h-full w-full' // Optional
});

// Module code (without import - component is globally available)
Popup.show(nsAlertPopup, {
    title: 'Information',
    message: 'This is an informational message'
});
```

**Parameters:**
- `title` (string): Alert title
- `message` (string): Alert message
- `size` (string): Optional size class
- `onAction` (function): Optional callback when OK is clicked

### 2. Confirm Popup (nsConfirmPopup)

Ask user for confirmation with Yes/No buttons.

```typescript
// Core application code (with import)
import nsConfirmPopup from '~/popups/ns-pos-confirm-popup.vue';

// Both core and module code can use directly
Popup.show(nsConfirmPopup, {
    title: 'Confirm Your Action',
    message: 'Are you sure you want to proceed?',
    onAction: (confirmed) => {
        if (confirmed) {
            // User clicked "Yes"
            performAction();
        }
    }
});
```

**Parameters:**
- `title` (string): Confirmation title
- `message` (string): Confirmation message
- `size` (string): Optional size class
- `onAction` (function): Callback with boolean result

**Common Pattern:**
```typescript
Popup.show(nsConfirmPopup, {
    title: __('Delete Item?'),
    message: __('This action cannot be undone. Continue?'),
    onAction: (confirmed) => {
        if (confirmed) {
            deleteItem();
        }
    }
});
```

### 3. Prompt Popup (nsPromptPopup)

Get text input from the user.

```typescript
// Core application code (with import)
import nsPromptPopup from '~/popups/ns-prompt-popup.vue';

// Both core and module code can use directly
const result = await new Promise((resolve, reject) => {
    Popup.show(nsPromptPopup, {
        title: 'Enter Value',
        message: 'Please provide a reason:',
        type: 'textarea', // or 'input'
        input: 'default value',
        resolve,
        reject
    });
});

console.log('User entered:', result);
```

**Parameters:**
- `title` (string): Prompt title
- `message` (string): Prompt message
- `type` (string): 'input' or 'textarea'
- `input` (string): Default input value
- `resolve` (function): Promise resolve
- `reject` (function): Promise reject
- `size` (string): Optional size class

### 4. Select Popup (nsSelectPopup)

Present a list of options for selection.

```typescript
// Core application code (with import)
import nsSelectPopup from '~/popups/ns-select-popup.vue';

// Both core and module code can use directly
try {
    const selected = await new Promise((resolve, reject) => {
        Popup.show(nsSelectPopup, {
            label: 'Choose Payment Method',
            description: 'Select how you want to pay',
            options: [
                { label: 'Cash', value: 'cash' },
                { label: 'Card', value: 'card' },
                { label: 'Mobile Money', value: 'mobile' }
            ],
            value: 'cash', // Pre-selected value
            type: 'select', // or 'multiselect'
            resolve,
            reject
        });
    });
    
    console.log('Selected:', selected);
} catch (error) {
    console.log('Selection cancelled');
}
```

**Parameters:**
- `label` (string): Popup title
- `description` (string): Optional description text
- `options` (array): Array of `{ label, value }` objects
- `value` (any): Pre-selected value(s)
- `type` (string): 'select' for single, 'multiselect' for multiple
- `resolve` (function): Promise resolve
- `reject` (function): Promise reject

**Multiselect Example:**
```typescript
const selected = await new Promise((resolve, reject) => {
    Popup.show(nsSelectPopup, {
        label: 'Select Categories',
        type: 'multiselect',
        options: categories.map(cat => ({
            label: cat.name,
            value: cat.id
        })),
        value: [], // Pre-selected values
        resolve,
        reject
    });
});

// Returns array of selected values
console.log('Selected IDs:', selected);
```

### 5. Media Popup (nsMediaPopup)

Upload and select media files.

```typescript
// Core application code (with import)
import nsMedia from '~/pages/dashboard/ns-media.vue';

// Both core and module code can use directly
const result = await new Promise((resolve, reject) => {
    Popup.show(nsMedia, {
        resolve,
        reject,
        type: 'url', // or 'model'
        user_id: 0 // Optional: filter by user
    });
});

if (result.event === 'use-selected') {
    const selectedMedia = result.value[0];
    console.log('Selected media URL:', selectedMedia.sizes.original);
    console.log('Selected media ID:', selectedMedia.id);
}
```

**Parameters:**
- `resolve` (function): Promise resolve
- `reject` (function): Promise reject
- `type` (string): 'url' for URL string, 'model' for media object
- `user_id` (number): Optional user ID filter

**Return Value:**
```typescript
{
    event: 'use-selected',
    value: [
        {
            id: 123,
            name: 'image.jpg',
            sizes: {
                thumb: '...',
                original: '...'
            },
            extension: 'jpg',
            // ... other media properties
        }
    ]
}
```

### 6. Loading Popup (nsPOSLoadingPopup)

Show a loading spinner.

```typescript
// Core application code (with import)
import nsPosLoadingPopup from '~/popups/ns-pos-loading-popup.vue';

// Both core and module code can use directly
const loadingPopup = Popup.show(nsPosLoadingPopup);

// Perform async operation
await fetchData();

// Close loading popup
loadingPopup.close();
```

## Creating Custom Popups

### Step 1: Create Popup Component

**Note:** Custom popup components created in modules should be self-contained. While built-in popup components are globally available, your custom module popups need to be properly imported within your module code.

Create your popup component following this structure:

```vue
<template>
    <div class="shadow-lg w-95vw md:w-2/3 lg:w-1/2 ns-box flex flex-col">
        <div class="p-2 border-b ns-box-header flex justify-between items-center">
            <h3>{{ title }}</h3>
            <div>
                <ns-close-button @click="close()"></ns-close-button>
            </div>
        </div>
        
        <div class="p-4 ns-box-body flex-auto overflow-y-auto">
            <!-- Your popup content here -->
            <p>{{ message }}</p>
        </div>
        
        <div class="p-2 border-t ns-box-footer flex justify-end">
            <ns-button @click="handleConfirm()" type="info">
                {{ __('Confirm') }}
            </ns-button>
            <ns-button @click="close()" type="error">
                {{ __('Cancel') }}
            </ns-button>
        </div>
    </div>
</template>

<script lang="ts">
import { __ } from '~/libraries/lang';
import popupCloser from '~/libraries/popup-closer';

export default {
    name: 'MyCustomPopup',
    props: ['popup'],
    
    data() {
        return {
            title: '',
            message: ''
        };
    },
    
    mounted() {
        // Initialize from params
        this.title = this.popup.params.title;
        this.message = this.popup.params.message;
        
        // Enable ESC key to close
        this.popupCloser();
    },
    
    methods: {
        __,
        
        close() {
            // Reject the promise if using promises
            if (this.popup.params.reject) {
                this.popup.params.reject(false);
            }
            this.popup.close();
        },
        
        handleConfirm() {
            const result = {
                confirmed: true,
                data: 'some data'
            };
            
            // Resolve the promise
            if (this.popup.params.resolve) {
                this.popup.params.resolve(result);
            }
            
            this.popup.close();
        }
    }
};
</script>
```

### Step 2: Use Your Custom Popup

```typescript
// In core application
import MyCustomPopup from '~/popups/my-custom-popup.vue';

// In modules - import from your module's path
import MyCustomPopup from './path/to/my-custom-popup.vue';

try {
    const result = await new Promise((resolve, reject) => {
        Popup.show(MyCustomPopup, {
            title: 'Custom Title',
            message: 'Custom message content',
            resolve,
            reject
        });
    });
    
    console.log('Result:', result);
} catch (error) {
    console.log('User cancelled');
}
```

## Advanced Patterns

### 1. Popup with Form Validation

```typescript
import nsCrudForm from '~/components/ns-crud-form.vue';

const result = await new Promise((resolve, reject) => {
    Popup.show(nsCrudForm, {
        src: '/api/products/form-config',
        submitUrl: '/api/products',
        submitMethod: 'post',
        resolve,
        reject
    });
});
```

### 2. Nested Popups

```typescript
// First popup
Popup.show(FirstPopup, {
    onAction: async () => {
        // Show second popup from within first
        try {
            const result = await new Promise((resolve, reject) => {
                Popup.show(SecondPopup, {
                    resolve,
                    reject
                });
            });
            
            // Process nested result
            processResult(result);
        } catch (error) {
            console.log('Nested popup cancelled');
        }
    }
});
```

### 3. Popup with Callback Pattern

```typescript
Popup.show(MyPopup, {
    onSubmit: (data) => {
        // Handle submission
        console.log('Submitted:', data);
    },
    onCancel: () => {
        // Handle cancellation
        console.log('Cancelled');
    }
});
```

### 4. Popup Configuration Options

```typescript
Popup.show(MyComponent, params, {
    primarySelector: '.custom-selector',
    popupClass: 'custom-class',
    closeOnOverlayClick: false
});
```

**Configuration Options:**
- `primarySelector` (string): Custom parent selector
- `popupClass` (string): Custom CSS classes for popup container
- `closeOnOverlayClick` (boolean): Whether clicking overlay closes popup

## Popup Lifecycle

### Opening a Popup

1. `Popup.show()` is called
2. Popup instance is created with unique hash
3. Component is added to state
4. Background is blurred
5. Popup animates in (zoom-out-entrance)
6. ESC key listener is attached

### Closing a Popup

1. `popup.close()` is called
2. Exit animation plays (zoom-in-exit)
3. After 250ms, popup is removed from state
4. Background blur is removed (if no other popups)
5. ESC key listener is destroyed
6. Optional callback is executed

## Helper Functions

### popupCloser

Enables closing popup with ESC key:

```typescript
import popupCloser from '~/libraries/popup-closer';

export default {
    mounted() {
        this.popupCloser();
    },
    methods: {
        popupCloser
    }
}
```

### popupResolver

Convenience method for resolving popups:

```typescript
import popupResolver from '~/libraries/popup-resolver';

export default {
    methods: {
        popupResolver,
        
        handleAction(result) {
            this.popupResolver(result);
        }
    }
}
```

## Best Practices

### 1. Always Use Promises for User Input

```typescript
// ✅ Good
const result = await new Promise((resolve, reject) => {
    Popup.show(MyPopup, { resolve, reject });
});

// ❌ Bad
Popup.show(MyPopup, {});
```

### 2. Handle Cancellation

```typescript
try {
    const result = await new Promise((resolve, reject) => {
        Popup.show(MyPopup, { resolve, reject });
    });
    
    // Process result
} catch (error) {
    // Always handle cancellation
    console.log('User cancelled');
}
```

### 3. Use Built-in Components When Possible

```typescript
// ✅ Good - Use built-in confirm
Popup.show(nsConfirmPopup, { ... });

// ❌ Bad - Don't create custom confirm unless needed
```

### 4. Provide Clear Titles and Messages

```typescript
// ✅ Good
Popup.show(nsConfirmPopup, {
    title: __('Delete Product?'),
    message: __('This will permanently delete the product. This action cannot be undone.')
});

// ❌ Bad
Popup.show(nsConfirmPopup, {
    title: 'Confirm',
    message: 'Are you sure?'
});
```

### 5. Clean Up Resources

```typescript
export default {
    data() {
        return {
            subscription: null
        };
    },
    
    mounted() {
        this.subscription = someObservable.subscribe(...);
    },
    
    unmounted() {
        // Always clean up
        if (this.subscription) {
            this.subscription.unsubscribe();
        }
    }
}
```

### 6. Use Proper Sizing

```typescript
// Standard sizes for consistency
const sizes = {
    small: 'w-[28.57vw]',      // ~30% width
    medium: 'w-[42.86vw]',     // ~43% width
    large: 'w-[57.14vw]',      // ~57% width
    xlarge: 'w-[71.43vw]',     // ~71% width
    full: 'w-[85.71vw]'        // ~86% width
};
```

## Common Use Cases

### 1. Confirm Before Delete

```typescript
Popup.show(nsConfirmPopup, {
    title: __('Confirm Deletion'),
    message: __('Are you sure you want to delete this item?'),
    onAction: (confirmed) => {
        if (confirmed) {
            nsHttpClient.delete(`/api/items/${id}`)
                .subscribe({
                    next: (result) => {
                        nsSnackBar.success(result.message);
                    },
                    error: (error) => {
                        nsSnackBar.error(error.message);
                    }
                });
        }
    }
});
```

### 2. Get User Input

```typescript
try {
    const reason = await new Promise((resolve, reject) => {
        Popup.show(nsPromptPopup, {
            title: __('Provide Reason'),
            message: __('Please explain why you are voiding this order:'),
            type: 'textarea',
            resolve,
            reject
        });
    });
    
    // Process with reason
    voidOrder(orderId, reason);
} catch (error) {
    console.log('User cancelled');
}
```

### 3. Select From Options

```typescript
try {
    const paymentType = await new Promise((resolve, reject) => {
        Popup.show(nsSelectPopup, {
            label: __('Select Payment Type'),
            options: paymentTypes.map(type => ({
                label: type.label,
                value: type.identifier
            })),
            resolve,
            reject
        });
    });
    
    processPayment(paymentType);
} catch (error) {
    console.log('Payment cancelled');
}
```

### 4. Upload Media

```typescript
try {
    const result = await new Promise((resolve, reject) => {
        Popup.show(nsMedia, { resolve, reject });
    });
    
    if (result.event === 'use-selected') {
        const mediaUrl = result.value[0].sizes.original;
        updateProductImage(mediaUrl);
    }
} catch (error) {
    console.log('Media selection cancelled');
}
```

## Troubleshooting

### Issue: Popup Not Closing

**Solution:** Ensure you're calling `this.popup.close()` and not just manipulating state.

```typescript
// ✅ Correct
this.popup.close();

// ❌ Wrong
this.showPopup = false;
```

### Issue: Background Not Blurring

**Solution:** Check that popup is being added to the correct parent wrapper.

### Issue: Multiple Popups Stacking

**Solution:** This is supported behavior. Each popup gets its own blur layer.

### Issue: ESC Key Not Working

**Solution:** Make sure you're calling `popupCloser()` in mounted hook.

```typescript
mounted() {
    this.popupCloser();
}
```

### Issue: Promise Never Resolves

**Solution:** Always call either `resolve()` or `reject()` before closing.

```typescript
methods: {
    close() {
        // Always reject before closing
        if (this.popup.params.reject) {
            this.popup.params.reject(false);
        }
        this.popup.close();
    }
}
```

## TypeScript Support

### Type Definitions

```typescript
interface PopupConfig {
    primarySelector?: string;
    popupClass?: string;
    closeOnOverlayClick?: boolean;
}

interface PopupParams {
    resolve?: (value?: any) => void;
    reject?: (reason?: any) => void;
    [key: string]: any;
}

interface PopupInstance {
    hash: string;
    component: any;
    props: any;
    params: PopupParams;
    config: PopupConfig;
    close: (callback?: (popup: PopupInstance) => void) => void;
}
```

### Declaring Global Popup Components

For modules using TypeScript, declare the globally available popup components:

```typescript
// In your module's TypeScript file or type definition file
declare const nsAlertPopup: any;
declare const nsConfirmPopup: any;
declare const nsPromptPopup: any;
declare const nsSelectPopup: any;
declare const nsMedia: any;
declare const nsPosLoadingPopup: any;

// Now you can use them without imports
Popup.show(nsAlertPopup, {
    title: 'Alert',
    message: 'This is an alert'
});
```

### Usage with Types

```typescript
import { Popup } from '~/libraries/popup';
import type { PopupInstance } from '~/libraries/popup';

const popup: PopupInstance = Popup.show(MyComponent, {
    title: 'Hello',
    message: 'World'
});
```

This comprehensive guide covers all aspects of the NexoPOS Popup API, enabling developers to create consistent, user-friendly modal interactions throughout the application.
