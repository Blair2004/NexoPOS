---
applyTo: '**'
---

# NexoPOS nsHttpClient Guide

The `nsHttpClient` is a powerful HTTP client wrapper built on top of Axios that provides a reactive, Observable-based interface for making HTTP requests in NexoPOS. It's globally available throughout the application and offers enhanced error handling, request tracking, and integration with the NexoPOS hook system.

## Overview

The nsHttpClient is defined in `resources/ts/libraries/http-client.ts` and is automatically included in the global scope via `resources/ts/bootstrap.ts`. It's available in all Blade templates that include the API assets and in all Vue components.

## Global Availability

The nsHttpClient is available globally as:
- `window.nsHttpClient` - Global window object
- `nsHttpClient` - Direct global variable (in most contexts)

## Basic Usage

### GET Requests

```typescript
// Simple GET request
nsHttpClient.get('/api/products')
    .subscribe({
        next: (data) => {
            console.log('Products:', data);
        },
        error: (error) => {
            console.error('Error:', error);
        }
    });

// GET with configuration
nsHttpClient.get('/api/products', {
    headers: {
        'Accept': 'application/json'
    },
    params: {
        page: 1,
        limit: 10
    }
}).subscribe({
    next: (data) => {
        // Handle success
    },
    error: (error) => {
        // Handle error
    }
});
```

### POST Requests

```typescript
// Create a new product
const productData = {
    name: 'New Product',
    price: 29.99,
    category_id: 1
};

nsHttpClient.post('/api/products', productData)
    .subscribe({
        next: (response) => {
            console.log('Product created:', response);
        },
        error: (error) => {
            console.error('Creation failed:', error);
        }
    });

// POST with custom headers
nsHttpClient.post('/api/products', productData, {
    headers: {
        'Content-Type': 'application/json',
        'X-Custom-Header': 'value'
    }
}).subscribe({
    next: (response) => {
        // Handle success
    }
});
```

### PUT Requests

```typescript
// Update an existing product
const updateData = {
    name: 'Updated Product Name',
    price: 34.99
};

nsHttpClient.put('/api/products/123', updateData)
    .subscribe({
        next: (response) => {
            console.log('Product updated:', response);
        },
        error: (error) => {
            console.error('Update failed:', error);
        }
    });
```

### DELETE Requests

```typescript
// Delete a product
nsHttpClient.delete('/api/products/123')
    .subscribe({
        next: (response) => {
            console.log('Product deleted:', response);
        },
        error: (error) => {
            console.error('Deletion failed:', error);
        }
    });

// DELETE with configuration
nsHttpClient.delete('/api/products/123', {
    headers: {
        'X-Confirm-Delete': 'true'
    }
}).subscribe({
    next: (response) => {
        // Handle success
    }
});
```

## Advanced Features

### Observable Pattern

The nsHttpClient returns RxJS Observables, allowing for powerful reactive programming patterns:

```typescript
// Chain multiple requests
nsHttpClient.get('/api/categories')
    .pipe(
        switchMap(categories => {
            // Use first category to fetch products
            return nsHttpClient.get(`/api/products?category=${categories[0].id}`);
        })
    )
    .subscribe({
        next: (products) => {
            console.log('Products from first category:', products);
        }
    });

// Combine multiple requests
import { forkJoin } from 'rxjs';

forkJoin({
    products: nsHttpClient.get('/api/products'),
    categories: nsHttpClient.get('/api/categories'),
    users: nsHttpClient.get('/api/users')
}).subscribe({
    next: (results) => {
        console.log('All data loaded:', results);
    }
});
```

### Request Tracking

The nsHttpClient emits events that can be used to track request states:

```typescript
// Listen for request start/stop events
nsHttpClient.subject().subscribe(event => {
    switch (event.identifier) {
        case 'async.start':
            console.log('Request started:', event.url);
            // Show loading indicator
            break;
        case 'async.stop':
            console.log('Request completed');
            // Hide loading indicator
            break;
    }
});
```

### Custom Events

You can emit custom events through the HttpClient:

```typescript
// Emit a custom event
nsHttpClient.emit({
    identifier: 'custom.event',
    value: { message: 'Custom data' }
});
```

### Response Access

Access the last response data:

```typescript
nsHttpClient.get('/api/products').subscribe({
    next: (data) => {
        // Access full response object
        const fullResponse = nsHttpClient.response;
        console.log('Status:', fullResponse.status);
        console.log('Headers:', fullResponse.headers);
        console.log('Data:', data);
    }
});
```

## Error Handling

### Standard Error Handling

```typescript
nsHttpClient.post('/api/products', invalidData)
    .subscribe({
        next: (data) => {
            // Success handling
        },
        error: (error) => {
            if (error.status === 422) {
                // Validation errors
                console.log('Validation errors:', error.errors);
            } else if (error.status === 401) {
                // Unauthorized
                console.log('Authentication required');
            } else {
                // Other errors
                console.log('Request failed:', error.message);
            }
        }
    });
```

### Retry Logic

```typescript
import { retry, catchError } from 'rxjs/operators';
import { of } from 'rxjs';

nsHttpClient.get('/api/products')
    .pipe(
        retry(3), // Retry up to 3 times
        catchError(error => {
            console.error('All retries failed:', error);
            return of([]); // Return empty array as fallback
        })
    )
    .subscribe({
        next: (products) => {
            console.log('Products:', products);
        }
    });
```

## Integration with Vue Components

### In Vue Components

```typescript
export default {
    data() {
        return {
            products: [],
            loading: false
        };
    },
    
    mounted() {
        this.loadProducts();
    },
    
    methods: {
        loadProducts() {
            this.loading = true;
            
            nsHttpClient.get('/api/products')
                .subscribe({
                    next: (data) => {
                        this.products = data;
                        this.loading = false;
                    },
                    error: (error) => {
                        this.loading = false;
                        // Show error message
                        nsSnackBar.error('Failed to load products');
                    }
                });
        },
        
        createProduct(productData) {
            nsHttpClient.post('/api/products', productData)
                .subscribe({
                    next: (response) => {
                        // Add to local array
                        this.products.push(response);
                        nsSnackBar.success('Product created successfully');
                    },
                    error: (error) => {
                        nsSnackBar.error('Failed to create product');
                    }
                });
        }
    }
};
```

### With Composition API

```typescript
import { ref, onMounted } from 'vue';

export default {
    setup() {
        const products = ref([]);
        const loading = ref(false);
        
        const loadProducts = () => {
            loading.value = true;
            
            nsHttpClient.get('/api/products')
                .subscribe({
                    next: (data) => {
                        products.value = data;
                        loading.value = false;
                    },
                    error: () => {
                        loading.value = false;
                    }
                });
        };
        
        onMounted(() => {
            loadProducts();
        });
        
        return {
            products,
            loading,
            loadProducts
        };
    }
};
```

## CRUD Operations Pattern

### Complete CRUD Implementation

```typescript
class ProductService {
    
    // Create
    static create(productData) {
        return nsHttpClient.post('/api/products', productData);
    }
    
    // Read (all)
    static getAll(params = {}) {
        return nsHttpClient.get('/api/products', { params });
    }
    
    // Read (single)
    static getById(id) {
        return nsHttpClient.get(`/api/products/${id}`);
    }
    
    // Update
    static update(id, productData) {
        return nsHttpClient.put(`/api/products/${id}`, productData);
    }
    
    // Delete
    static delete(id) {
        return nsHttpClient.delete(`/api/products/${id}`);
    }
}

// Usage
ProductService.create({
    name: 'New Product',
    price: 29.99
}).subscribe({
    next: (product) => {
        console.log('Created:', product);
    }
});
```

## Hook System Integration

The nsHttpClient integrates with NexoPOS's hook system:

```typescript
// URLs are filtered through the hook system
// You can modify URLs before requests are made
nsHooks.addFilter('http-client-url', (url) => {
    // Modify URL if needed
    if (url.includes('/api/')) {
        return url + '?timestamp=' + Date.now();
    }
    return url;
});
```

## Best Practices

### 1. Consistent Error Handling

```typescript
// Create a centralized error handler
const handleError = (error) => {
    if (error.status === 422) {
        // Handle validation errors
        Object.keys(error.errors || {}).forEach(field => {
            nsSnackBar.error(`${field}: ${error.errors[field][0]}`);
        });
    } else if (error.status === 401) {
        // Handle authentication
        window.location.href = '/login';
    } else {
        // Generic error
        nsSnackBar.error(error.message || 'An error occurred');
    }
};

// Use in requests
nsHttpClient.post('/api/products', data)
    .subscribe({
        next: (response) => {
            // Handle success
        },
        error: handleError
    });
```

### 2. Loading States

```typescript
// Track loading states globally
let activeRequests = 0;

nsHttpClient.subject().subscribe(event => {
    if (event.identifier === 'async.start') {
        activeRequests++;
        // Show global loading indicator
    } else if (event.identifier === 'async.stop') {
        activeRequests--;
        if (activeRequests === 0) {
            // Hide global loading indicator
        }
    }
});
```

### 3. Request Caching

```typescript
import { shareReplay } from 'rxjs/operators';

// Cache frequently accessed data
const cachedCategories = nsHttpClient.get('/api/categories')
    .pipe(shareReplay(1)); // Cache and replay for new subscribers

// Multiple components can subscribe without making multiple requests
cachedCategories.subscribe(data => console.log('Component 1:', data));
cachedCategories.subscribe(data => console.log('Component 2:', data));
```

### 4. Request Debouncing

```typescript
import { debounceTime, distinctUntilChanged, switchMap } from 'rxjs/operators';
import { fromEvent } from 'rxjs';

// Debounce search requests
const searchInput = document.getElementById('search');

fromEvent(searchInput, 'input')
    .pipe(
        debounceTime(300),
        distinctUntilChanged(),
        switchMap(event => 
            nsHttpClient.get(`/api/products/search?q=${event.target.value}`)
        )
    )
    .subscribe({
        next: (results) => {
            // Update search results
        }
    });
```

## Common Patterns

### Form Submission

```typescript
// Handle form submission with validation
const submitForm = (formData) => {
    nsHttpClient.post('/api/products', formData)
        .subscribe({
            next: (response) => {
                nsSnackBar.success('Product created successfully');
                // Reset form or redirect
            },
            error: (error) => {
                if (error.status === 422) {
                    // Display validation errors
                    displayValidationErrors(error.errors);
                } else {
                    nsSnackBar.error('Failed to create product');
                }
            }
        });
};
```

### Pagination

```typescript
const loadPage = (page = 1, limit = 10) => {
    nsHttpClient.get('/api/products', {
        params: { page, limit }
    }).subscribe({
        next: (response) => {
            // response typically contains: data, current_page, last_page, total
            this.products = response.data;
            this.currentPage = response.current_page;
            this.totalPages = response.last_page;
        }
    });
};
```

### File Upload

```typescript
const uploadFile = (file) => {
    const formData = new FormData();
    formData.append('file', file);
    
    nsHttpClient.post('/api/upload', formData, {
        headers: {
            'Content-Type': 'multipart/form-data'
        }
    }).subscribe({
        next: (response) => {
            console.log('File uploaded:', response.file_path);
        }
    });
};
```

## Security Considerations

### CSRF Protection

The nsHttpClient automatically handles CSRF tokens when using the default Axios configuration from NexoPOS.

### Authentication Headers

Authentication headers are typically handled automatically, but you can add custom headers:

```typescript
// Add authorization header
nsHttpClient.post('/api/admin/products', data, {
    headers: {
        'Authorization': 'Bearer ' + authToken
    }
}).subscribe({
    next: (response) => {
        // Handle response
    }
});
```

## Troubleshooting

### Common Issues

1. **CORS Errors**: Ensure your API routes are properly configured for CORS
2. **Authentication Issues**: Check that CSRF tokens and session cookies are properly set
3. **Observable Not Completing**: Always handle both success and error cases in subscribe

### Debugging

```typescript
// Enable request/response logging
nsHttpClient.subject().subscribe(event => {
    console.log('HTTP Event:', event);
});

// Log all requests
const originalRequest = nsHttpClient._request;
nsHttpClient._request = function(...args) {
    console.log('Making request:', args);
    return originalRequest.apply(this, args);
};
```

The nsHttpClient provides a powerful, reactive interface for handling HTTP requests in NexoPOS applications, with built-in error handling, request tracking, and seamless integration with the framework's architecture.
