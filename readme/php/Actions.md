Actions are events that fires at a specific moment on the application process. 
The idea behind this, is to perform a specific action at a certain time during the application execution. 

The main difference between actions and filters is that the value provided on an action is not meant to be mutated (unless it's an object). With filter, the provided parametes can be changed and the new value is then returned.

| Action                 | Description                             |
|------------------------|-----------------------------------------|
| ns-dashboard-footer    | Provides an Output object to inject content at the footer of the page |
