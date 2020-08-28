## Form Validation
The Form validation helps to make sure a form is valid before submition. On NexoPOS 4.x a reactive approach is used to build a form. That ensures dynamic form creation (from remote server). The component `ns-input`, `ns-select`, `ns-radio`, `ns-datetime`, `ns-date`, `ns-textarea` are all compatible with the validation. Refer to each components to understand how the integration is made.

The Form Validation class is available on "resources/js/libraries/form-validation.js". This class expose some methods the helps interactif with a Form Array. A Form Array consist of list of fields. 

Here is how to defined a field.

### Creating An Array Of Fields
```js
const myFields    =   [
    {
        'label' =>  'Your Username',
        'name'  =>  'username',
        'description'   =>  '',
        'validation'    =>  'required',
    }
];
```

While looping the fields, the Form Validation class uses the `validation` attribute of each field as a reference for the validation. On the previous example, the rule `required` is applied. But a validation my contains more than one rule, splitted by the `|` character. 

### Init Validation Class On Fields
Ideally, it's recommended to initialize an array of field using the `createForm` method of the Form Validation class. This method ensures that the form is populated with useful properties (for the validation). This can be made this way :

```js
const myFields    =   [
    {
        'label' =>  'Your Username',
        'name'  =>  'username',
        'description'   =>  '',
        'validation'    =>  'required',
    }
];
const form      =   require( './path/to/form-validation' );
const fields    =   form.createForm( myFields );
```

### Render Forms Dynamically
As we've said that a reactive approach is used, you can use existing form components to render your form. This should be made this way.

```html
<template v-for="field of fields">
    <ns-input v-if="[ 'text', 'password' ].includes( field.type )" :field="field" @change="form.validateField( field )"></ns-input>
</template>
```

### Form Validation
Before proceeding to the validaton, it's required to make sure the provided data are valid using the ruleset defined on each field. This can be made using the `validateForm` methods which accepts an array of fields (initialized with `createForm`). Here is how you can acheive that : 

```js
// within a component
methods: {
    submitForm() {
        if ( this.form.validateForm( this.fields ) ) {
            // means the form is valid.
        }
    }
}
// ...
```
### Extracting Form Value
If the provided form consist of an array of Field, then you'll need to extract the field value before submitting the form to the server. Ideally this is how you'll proceed :

```js
// assuming this.form is an instance of FormValidation
const data =    this.form.extractFields( this.fields );
nsHttpClient.post( '/foo/bar', data ).subscribe( _ => ... );
```
