# ns-media-input

This file helps to retreive a link or a module (as a js object) from the media library. This field should likely be used to attach media to components. 
The field can either receive a link to the media or the object that will be provided as the value of a `Field`

## Using Media URL

```vue
<script>
export default {
  data() {
    return {
      field: {
        label: 'Your Avatar',
        name: 'avatar',
        type: 'media',
        description: 'This will use direct URL to the avatar',
      }
    }
  }
}
</script>
<template>
  <ns-media-input :field="field"></ns-media-input>
<template>
```

Here the value of the field `field.value` will contain the URL to the image, so here there is no possible way to link the field to the Media model.

## Using Media Model

```vue
<script>
export default {
  data() {
    return {
      field: {
        label: 'Your Avatar',
        name: 'avatar',
        type: 'media',
        data: [
          type: 'model',
        ],
        description: 'This will use Media model of the avatar',
      }
    }
  }
}
</script>
<template>
  <ns-media-input :field="field"></ns-media-input>
<template>
```

The value of the field `field.value` will contain an object including the model name, id and differents sizes.
