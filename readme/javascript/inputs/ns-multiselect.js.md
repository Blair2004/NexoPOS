# ns-multiselect
This is a multiselect form input that allow to proceed a multi selection of provided options.
It support a `Field` instance and output two event when an option is checked or unchecked.

```vue
<script>
export default {
  data() {
    return {
      field: {
        label: 'Favorite Movies',
        type: 'multiselect',
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
        description: 'Select your favorite movie',
      }
    }
  }
}
</script>
<template>
  <ns-multiselect :field="field"></ns-multiselect>
<template>
```
