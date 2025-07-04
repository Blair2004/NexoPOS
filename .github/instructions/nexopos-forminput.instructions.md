---
applyTo: '**'
---

# NexoPOS FormInput Class Usage Guide

The `FormInput` class is a utility class located at `app/Classes/FormInput.php` that provides static methods for creating standardized form input configurations. This class simplifies the creation of various form field types with consistent structure and validation.

## Class Overview

The FormInput class follows a consistent pattern where each method returns an associative array containing the field configuration. All input types support common parameters like validation, description, disabled state, and conditional display.

## Common Parameters

Most FormInput methods accept these common parameters:

- `$label` (string): The display label for the input field
- `$name` (string): The name attribute for the input field
- `$value` (string): The default value of the input field
- `$validation` (string): Validation rules for the input field
- `$description` (string): Help text or description for the field
- `$disabled` (bool): Whether the field should be disabled
- `$show` (callable|null): A callable that determines field visibility

## Available Input Types

### Text-based Inputs

#### Basic Text Input
```php
FormInput::text($label, $name, $value = '', $validation = '', $description = '', $disabled = false, $type = 'text', $errors = [], $data = [], $show = null)
```

**Example:**
```php
FormInput::text(
    label: 'Full Name',
    name: 'full_name',
    validation: 'required|string|max:255',
    description: 'Enter your full name'
)
```

#### Password Input
```php
FormInput::password($label, $name, $value = '', $validation = '', $description = '', $disabled = false, $show = null)
```

**Example:**
```php
FormInput::password(
    label: 'Password',
    name: 'password',
    validation: 'required|min:8',
    description: 'Must be at least 8 characters'
)
```

#### Email Input
```php
FormInput::email($label, $name, $value = '', $validation = '', $description = '', $disabled = false, $show = null)
```

**Example:**
```php
FormInput::email(
    label: 'Email Address',
    name: 'email',
    validation: 'required|email',
    description: 'Enter a valid email address'
)
```

#### Number Input
```php
FormInput::number($label, $name, $value = '', $validation = '', $description = '', $disabled = false, $errors = [], $show = null)
```

**Example:**
```php
FormInput::number(
    label: 'Age',
    name: 'age',
    validation: 'required|integer|min:18',
    description: 'Must be 18 or older'
)
```

#### Telephone Input
```php
FormInput::tel($label, $name, $value = '', $validation = '', $description = '', $disabled = false, $type = 'tel', $show = null)
```

**Example:**
```php
FormInput::tel(
    label: 'Phone Number',
    name: 'phone',
    validation: 'required',
    description: 'Enter your phone number'
)
```

#### Hidden Input
```php
FormInput::hidden($name, $label = '', $value = '', $validation = '', $description = '', $disabled = false, $errors = [], $show = null)
```

**Example:**
```php
FormInput::hidden(name: 'user_id', value: '123')
```

### Date and Time Inputs

#### Date Input
```php
FormInput::date($label, $name, $value = '', $validation = '', $description = '', $disabled = false, $show = null)
```

**Example:**
```php
FormInput::date(
    label: 'Birth Date',
    name: 'birth_date',
    validation: 'required|date',
    description: 'Select your birth date'
)
```

#### DateTime Picker
```php
FormInput::datetime($label, $name, $value = '', $validation = '', $description = '', $disabled = false, $show = null)
```

**Example:**
```php
FormInput::datetime(
    label: 'Appointment Time',
    name: 'appointment_time',
    validation: 'required',
    description: 'Select date and time'
)
```

#### Date Range Picker
```php
FormInput::daterange($label, $name, $value = '', $validation = '', $description = '', $disabled = false, $show = null)
```

**Example:**
```php
FormInput::daterange(
    label: 'Reporting Period',
    name: 'date_range',
    validation: 'required',
    description: 'Select start and end dates'
)
```

### Selection Inputs

#### Select Dropdown
```php
FormInput::select($label, $name, $options, $value = '', $validation = '', $description = '', $disabled = false, $type = 'select', $component = '', $props = [], $refresh = false, $errors = [], $show = null)
```

**Example:**
```php
FormInput::select(
    label: 'Country',
    name: 'country',
    options: [
        ['label' => 'United States', 'value' => 'US'],
        ['label' => 'Canada', 'value' => 'CA'],
        ['label' => 'Mexico', 'value' => 'MX']
    ],
    validation: 'required',
    description: 'Select your country'
)
```

#### Searchable Select
```php
FormInput::searchSelect($label, $name, $value = '', $options = [], $validation = '', $description = '', $disabled = false, $component = '', $props = [], $refresh = false, $errors = [], $show = null)
```

**Example:**
```php
FormInput::searchSelect(
    label: 'Product',
    name: 'product_id',
    validation: 'required',
    description: 'Search and select a product'
)
```

#### Multiselect
```php
FormInput::multiselect($label, $name, $options, $value = '', $validation = '', $description = '', $disabled = false, $show = null)
```

**Example:**
```php
FormInput::multiselect(
    label: 'Categories',
    name: 'categories',
    options: [
        ['label' => 'Electronics', 'value' => 'electronics'],
        ['label' => 'Clothing', 'value' => 'clothing'],
        ['label' => 'Books', 'value' => 'books']
    ],
    validation: 'required',
    description: 'Select one or more categories'
)
```

#### Inline Multiselect
```php
FormInput::inlineMultiselect($label, $name, $value, $options, $validation = '', $description = '', $disabled = false, $show = null)
```

#### Switch/Toggle
```php
FormInput::switch($label, $name, $options, $value = '', $validation = '', $description = '', $disabled = false, $errors = [], $show = null)
```

**Example:**
```php
FormInput::switch(
    label: 'Enable Notifications',
    name: 'notifications_enabled',
    options: [
        ['label' => 'Yes', 'value' => 1],
        ['label' => 'No', 'value' => 0]
    ],
    value: 1,
    description: 'Enable or disable notifications'
)
```

### Text Area and Rich Text

#### Textarea
```php
FormInput::textarea($label, $name, $value = '', $validation = '', $description = '', $disabled = false, $data = [], $show = null)
```

**Example:**
```php
FormInput::textarea(
    label: 'Description',
    name: 'description',
    validation: 'required|max:1000',
    description: 'Enter a detailed description'
)
```

#### CKEditor (Rich Text)
```php
FormInput::ckeditor($label, $name, $value = '', $validation = '', $description = '', $disabled = false, $show = null)
```

**Example:**
```php
FormInput::ckeditor(
    label: 'Content',
    name: 'content',
    validation: 'required',
    description: 'Enter rich text content'
)
```

### Special Input Types

#### Checkbox
```php
FormInput::checkbox($label, $name, $value = '', $validation = '', $description = '', $disabled = false, $show = null)
```

**Example:**
```php
FormInput::checkbox(
    label: 'I agree to terms',
    name: 'agree_terms',
    value: '1',
    validation: 'required',
    description: 'Check to agree to terms and conditions'
)
```

#### Media Upload
```php
FormInput::media($label, $name, $value = '', $validation = '', $description = '', $disabled = false, $errors = [], $data = ['type' => 'url'], $show = null)
```

**Example:**
```php
FormInput::media(
    label: 'Profile Image',
    name: 'profile_image',
    description: 'Upload your profile image'
)
```

#### Select Audio
```php
FormInput::selectAudio($label, $name, $value, $options, $validation = '', $description = '', $disabled = false, $show = null)
```

#### Custom Component
```php
FormInput::custom($label, $component, $show = null)
```

**Example:**
```php
FormInput::custom(
    label: 'Custom Widget',
    component: 'MyCustomComponent'
)
```

## Advanced Features

### Refresh Configuration
For dynamic select fields that need to refresh based on other field changes:

```php
FormInput::refreshConfig($url, $watch, $data = [])
```

**Example:**
```php
$cityField = FormInput::searchSelect(
    label: 'City',
    name: 'city_id',
    validation: 'required',
    description: 'Select a city'
);
$cityField['refresh'] = FormInput::refreshConfig(
    url: '/api/cities',
    watch: 'country_id'
);
```

### Conditional Display
Use the `$show` parameter to conditionally display fields:

```php
FormInput::text(
    label: 'Company Name',
    name: 'company_name',
    validation: 'required',
    description: 'Enter company name',
    show: function($data) {
        return $data['user_type'] === 'business';
    }
)
```

## Usage Patterns

### Creating a Form Configuration Array
```php
$formFields = [
    FormInput::text(
        label: 'Name',
        name: 'name',
        validation: 'required|string|max:255'
    ),
    FormInput::email(
        label: 'Email',
        name: 'email',
        validation: 'required|email'
    ),
    FormInput::select(
        label: 'Role',
        name: 'role',
        options: [
            ['label' => 'Admin', 'value' => 'admin'],
            ['label' => 'User', 'value' => 'user']
        ],
        validation: 'required'
    ),
    FormInput::checkbox(
        label: 'Active',
        name: 'is_active',
        value: '1'
    ),
    FormInput::textarea(
        label: 'Notes',
        name: 'notes',
        description: 'Optional notes'
    )
];
```

### Dynamic Field Generation
```php
public function getFormFields($userType = null)
{
    $fields = [
        FormInput::text(
            label: 'Name',
            name: 'name',
            validation: 'required'
        ),
        FormInput::email(
            label: 'Email',
            name: 'email',
            validation: 'required|email'
        )
    ];

    if ($userType === 'business') {
        $fields[] = FormInput::text(
            label: 'Company',
            name: 'company',
            validation: 'required'
        );
        $fields[] = FormInput::tel(
            label: 'Business Phone',
            name: 'business_phone'
        );
    }

    return $fields;
}
```

## Best Practices

1. **Validation Rules**: Always include appropriate validation rules for data integrity
2. **Descriptions**: Provide helpful descriptions for complex fields
3. **Consistent Naming**: Use consistent naming conventions for field names
4. **Error Handling**: Include error arrays when needed for form validation feedback
5. **Conditional Logic**: Use the `$show` parameter for dynamic form behavior
6. **Option Arrays**: For select fields, ensure options follow the `['label' => '', 'value' => '']` format
7. **Default Values**: Set sensible default values where appropriate

## Integration with NexoPOS

The FormInput class is designed to work seamlessly with NexoPOS's form rendering system. The returned arrays are typically used in:

- CRUD class field definitions
- Form configuration arrays
- Dynamic form builders
- Module form configurations

The class ensures consistent form field structure across the entire NexoPOS application while providing flexibility for custom implementations.
