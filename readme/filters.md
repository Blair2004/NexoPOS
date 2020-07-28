# Introduction
Filters is part of the Hook api of NexoPOS that helps to extends the application. 
Filters are values that can be mutated by subscribers, while actions are even that can be listened by subscribers. This documents describes filters and list all 
available filters as long as their purpose and arguments.

| Filter | Description | Arguments
| ------ | ----------- | -------- |
| {namespace}-catch-action | Helps to catch bulk action of a specific CRUD component. {namespace} should be replaced by the actual CRUD component namespace(identifier). | 2 (`boolean`, `Illuminate\Http\Request)`|
| {namespace}-crud-actions | Helps to add actions or various other information on each row part of the result.data array. | 1 (`Illuminate\Database\Eloquent\Model`)|
| ns-crud-resource | Used to return relevant CRUD component class when there is a match with the identifier(namespace). | 2 ( `<string>namespace`, `<number>?identifier` )|
| ns-crud-form | Used to hold form as defined on the `getForm` on the CRUD component class. | 3 ( `<array>form`, `<string>namespace`, `<array>(model, namespace, id)` )|
| ns-validation | Contains the validation rules for a specific CRUD Component class. | 1 ( `<array>validation` )|
