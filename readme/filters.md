# Introduction
Filters is part of the Hook api of NexoPOS that helps to extends the application. 
Filters are values that can be mutated by subscribers, while actions are even that can be listened by subscribers. This documents describes filters and list all 
available filters as long as their purpose and arguments.

| Filter | Description | Arguments
| ------ | ----------- | -------- |
| {namespace}-catch-action | Helps to catch bulk action of a specific CRUD component. {namespace} should be replaced by the actual CRUD component namespace(identifier). | 2 (boolean, `Illuminate\Http\Request)`|
