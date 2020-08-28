# ns-switch
This field act as a select option, but should be used for a 2 values options. It's not a checkbox option in the way it always provide a value.
It also support as a parameter `Field`. On option change, the field emit a @changed event.

```vue
<script>
export default {
  data() {
    return {
      field: {
        label: 'Allow Registeration',
        type: 'switch',
        options: [
          {
            label: 'Yes',
            value: 'yes',
          }, {
            label: 'No',
            value: 'no',
          }, 
        ],
        description: 'would you grant authentication',
      }
    }
  }
}
</script>
<template>
  <ns-switch :field="field"></ns-switch>
<template>
```
