# ns-select
This use the regular `select` tags provided by HTML. This should be used only to give one choice to the user.
On change, the field emit a changed event. This input support the `Field` instance.


```vue
<script>
export default {
  data() {
    return {
      field: {
        label: 'Best Movie',
        type: 'select',
        options: [
          {
            label: 'Joker',
            value: 'joker',
          }, {
            label: 'Avenger Endgame',
            value: 'avenger_endgame',
          }, {
            label: 'Taken',
            value: 'taken',
          }
        ],
        description: 'Select your best movie',
      }
    }
  }
}
</script>
<template>
  <ns-select :field="field"></ns-select>
<template>
```
