# NexoPOS ns-tabs and ns-tabs-item Usage Guide

The `ns-tabs` and `ns-tabs-item` components work together to create a tabbed interface in NexoPOS. The `ns-tabs` component acts as the container that manages tab state and navigation, while `ns-tabs-item` components represent individual tab content.

## Component Overview

### ns-tabs (Parent Component)
The `ns-tabs` component provides the tab navigation header and manages the active state of child tabs.

### ns-tabs-item (Child Component)  
The `ns-tabs-item` component represents individual tab content that is shown/hidden based on the active tab.

## Basic Usage

### Simple Tab Structure
```vue
<template>
  <ns-tabs :active="activeTab" @active="handleTabChange">
    <ns-tabs-item 
      :label="__('First Tab')" 
      identifier="tab1">
      <p>Content for the first tab</p>
    </ns-tabs-item>
    
    <ns-tabs-item 
      :label="__('Second Tab')" 
      identifier="tab2">
      <p>Content for the second tab</p>
    </ns-tabs-item>
  </ns-tabs>
</template>

<script>
export default {
  data() {
    return {
      activeTab: 'tab1'
    }
  },
  methods: {
    handleTabChange(tabIdentifier) {
      this.activeTab = tabIdentifier;
      // Handle tab change logic
    }
  }
}
</script>
```

## Component Properties

### ns-tabs Props
- `active` (String): The identifier of the currently active tab

### ns-tabs-item Props
- `label` (String, required): The display text for the tab header
- `identifier` (String, required): Unique identifier for the tab
- `closable` (Boolean, optional): Whether the tab can be closed with an X button
- `padding` (String, optional): Custom padding class (default: 'p-4')

### ns-tabs Attributes
- `visible` (String): Set to 'true' or 'false' to control tab visibility
- All props can also be passed as HTML attributes

## Events

### ns-tabs Events
- `@active`: Fired when a tab is clicked/activated, receives the tab identifier
- `@changeTab`: Alias for `@active`, fired when tab changes
- `@close`: Fired when a closable tab's close button is clicked, receives the tab object

## Advanced Examples

### 1. Tabs with Custom Padding
```vue
<ns-tabs :active="currentTab" @active="setActiveTab">
  <ns-tabs-item 
    :label="__('Settings')" 
    identifier="settings"
    padding="0">
    <!-- Content without default padding -->
    <div class="p-2 border-b">
      <ns-field v-for="field in fields" :field="field" :key="field.name"></ns-field>
    </div>
    <div class="flex justify-end p-2">
      <ns-button type="info">{{ __('Save') }}</ns-button>
    </div>
  </ns-tabs-item>
  
  <ns-tabs-item 
    :label="__('Summary')" 
    identifier="summary"
    padding="p-6">
    <!-- Content with custom padding -->
    <div>Summary content with extra padding</div>
  </ns-tabs-item>
</ns-tabs>
```

### 2. Closable Tabs
```vue
<ns-tabs :active="activeTab" @active="setActiveTab" @close="closeTab">
  <ns-tabs-item 
    :label="__('Document 1')" 
    identifier="doc1"
    :closable="true">
    <p>Document 1 content</p>
  </ns-tabs-item>
  
  <ns-tabs-item 
    :label="__('Document 2')" 
    identifier="doc2"
    :closable="true">
    <p>Document 2 content</p>
  </ns-tabs-item>
</ns-tabs>

<script>
export default {
  data() {
    return {
      activeTab: 'doc1',
      tabs: ['doc1', 'doc2']
    }
  },
  methods: {
    setActiveTab(tabId) {
      this.activeTab = tabId;
    },
    closeTab(tab) {
      // Remove tab from list
      this.tabs = this.tabs.filter(t => t !== tab.identifier);
      
      // If closing active tab, switch to another tab
      if (tab.identifier === this.activeTab && this.tabs.length > 0) {
        this.activeTab = this.tabs[0];
      }
    }
  }
}
</script>
```

### 3. Conditional Tab Visibility
```vue
<ns-tabs :active="activeTab" @active="setActiveTab">
  <ns-tabs-item 
    :label="__('Basic Info')" 
    identifier="basic"
    visible="true">
    <p>Always visible basic information</p>
  </ns-tabs-item>
  
  <ns-tabs-item 
    :label="__('Advanced Settings')" 
    identifier="advanced"
    :visible="userRole === 'admin' ? 'true' : 'false'">
    <p>Only visible to admins</p>
  </ns-tabs-item>
  
  <ns-tabs-item 
    :label="__('Debug Info')" 
    identifier="debug"
    :visible="debugMode ? 'true' : 'false'">
    <p>Only visible in debug mode</p>
  </ns-tabs-item>
</ns-tabs>
```

### 4. Tabs with Extra Header Content
```vue
<ns-tabs :active="activeTab" @active="setActiveTab">
  <template #extra>
    <div class="flex items-center space-x-2">
      <ns-button size="sm" type="info" @click="refreshData">
        <i class="las la-sync-alt mr-1"></i>{{ __('Refresh') }}
      </ns-button>
      <ns-button size="sm" type="success" @click="addNewTab">
        <i class="las la-plus mr-1"></i>{{ __('Add Tab') }}
      </ns-button>
    </div>
  </template>
  
  <ns-tabs-item 
    :label="__('Data View')" 
    identifier="data">
    <div>Your data content here</div>
  </ns-tabs-item>
  
  <ns-tabs-item 
    :label="__('Settings')" 
    identifier="settings">
    <div>Settings content here</div>
  </ns-tabs-item>
</ns-tabs>
```

### 5. Dynamic Tabs
```vue
<template>
  <ns-tabs :active="activeTab" @active="setActiveTab" @close="closeTab">
    <ns-tabs-item 
      v-for="tab in dynamicTabs"
      :key="tab.id"
      :label="tab.title"
      :identifier="tab.id"
      :closable="tab.closable">
      <component :is="tab.component" v-bind="tab.props"></component>
    </ns-tabs-item>
  </ns-tabs>
</template>

<script>
export default {
  data() {
    return {
      activeTab: 'overview',
      dynamicTabs: [
        {
          id: 'overview',
          title: this.__('Overview'),
          component: 'OverviewComponent',
          closable: false,
          props: {}
        },
        {
          id: 'details',
          title: this.__('Details'),
          component: 'DetailsComponent', 
          closable: true,
          props: { itemId: 123 }
        }
      ]
    }
  },
  methods: {
    setActiveTab(tabId) {
      this.activeTab = tabId;
    },
    closeTab(tab) {
      this.dynamicTabs = this.dynamicTabs.filter(t => t.id !== tab.identifier);
      if (tab.identifier === this.activeTab && this.dynamicTabs.length > 0) {
        this.activeTab = this.dynamicTabs[0].id;
      }
    },
    addTab(tabConfig) {
      this.dynamicTabs.push(tabConfig);
      this.activeTab = tabConfig.id;
    }
  }
}
</script>
```

## Real-World Examples from NexoPOS

### Product Preview Popup
```vue
<ns-tabs :active="active" @active="changeActiveTab($event)">
  <ns-tabs-item :label="__('Units & Quantities')" identifier="units-quantities">
    <table class="table ns-table w-full" v-if="hasLoadedUnitQuantities">
      <thead>
        <tr>
          <th class="p-1 border">{{ __('Unit') }}</th>
          <th width="150" class="text-right p-1 border">{{ __('Sale Price') }}</th>
          <th width="150" class="text-right p-1 border">{{ __('Wholesale Price') }}</th>
          <th width="150" class="text-right p-1 border">{{ __('Quantity') }}</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="unitQuantity of unitQuantities" :key="unitQuantity.id">
          <td class="p-1 border text-left">{{ unitQuantity.unit.name }}</td>
          <td class="p-1 border text-right">{{ nsCurrency(unitQuantity.sale_price) }}</td>
          <td class="p-1 border text-right">{{ nsCurrency(unitQuantity.wholesale_price) }}</td>
          <td class="p-1 border text-right">{{ unitQuantity.quantity }}</td>
        </tr>
      </tbody>
    </table>
    <ns-spinner v-if="!hasLoadedUnitQuantities" size="16" border="4"></ns-spinner>
  </ns-tabs-item>
</ns-tabs>
```

### Tax & Summary Popup
```vue
<ns-tabs v-if="Object.keys(options).length > 0" :active="activeTab" @changeTab="changeActive($event)">
  <ns-tabs-item padding="0" :label="__('Settings')" identifier="settings" :active="true">
    <div class="p-2 border-b ns-box-body">
      <ns-field v-for="(field,index) of group_fields" :field="field" :key="index"></ns-field>
    </div>
    <div class="flex justify-end p-2">
      <ns-button @click="saveTax()" type="info">{{ __('Save') }}</ns-button>
    </div>
  </ns-tabs-item>
  
  <ns-tabs-item padding="0" :label="__('Summary')" identifier="summary" :active="false">
    <div class="p-2" v-if="order">
      <div v-for="tax of order.taxes" :key="tax.id" 
           class="mb-2 border shadow p-2 w-full flex justify-between items-center elevation-surface">
        <span>{{ tax.name }}</span>
        <span>{{ nsCurrency(tax.tax_value) }}</span>
      </div>
      <div class="p-2 text-center text-font" v-if="order.taxes.length === 0">
        {{ __('No tax is active') }}
      </div>
    </div>
  </ns-tabs-item>
</ns-tabs>
```

## Best Practices

### 1. Tab Identification
- Always use unique, descriptive identifiers for tabs
- Use kebab-case for identifiers (e.g., 'user-settings', 'order-history')

### 2. Internationalization
- Wrap all labels with the `__()` translation function
- Use descriptive translation keys

### 3. State Management
- Keep track of the active tab in component data
- Handle tab changes appropriately
- Consider persistence for user preference

### 4. Performance
- Use conditional rendering for heavy content
- Consider lazy loading for tab content that requires API calls

### 5. Accessibility
- Ensure tab labels are descriptive
- Use appropriate ARIA attributes when extending the component

### 6. Error Handling
- Validate that the active tab exists in the tabs list
- Provide fallback for missing tabs
- Handle edge cases when all tabs are closed

## Common Patterns

### Loading States in Tabs
```vue
<ns-tabs-item :label="__('Data')" identifier="data">
  <div v-if="loading" class="flex justify-center p-8">
    <ns-spinner size="24" border="4"></ns-spinner>
  </div>
  <div v-else>
    <!-- Loaded content -->
  </div>
</ns-tabs-item>
```

### Form Tabs with Validation
```vue
<ns-tabs :active="activeTab" @active="setActiveTab">
  <ns-tabs-item :label="getTabLabel('basic')" identifier="basic">
    <ns-field v-for="field in basicFields" :field="field" :key="field.name"></ns-field>
  </ns-tabs-item>
  
  <ns-tabs-item :label="getTabLabel('advanced')" identifier="advanced">
    <ns-field v-for="field in advancedFields" :field="field" :key="field.name"></ns-field>
  </ns-tabs-item>
</ns-tabs>

<script>
methods: {
  getTabLabel(tabId) {
    const hasErrors = this.hasValidationErrors(tabId);
    const label = this.tabLabels[tabId];
    return hasErrors ? `${label} ⚠️` : label;
  },
  hasValidationErrors(tabId) {
    const fields = tabId === 'basic' ? this.basicFields : this.advancedFields;
    return fields.some(field => field.errors && field.errors.length > 0);
  }
}
</script>
```

This guide covers the comprehensive usage of `ns-tabs` and `ns-tabs-item` components in NexoPOS, from basic implementation to advanced patterns and real-world examples.
