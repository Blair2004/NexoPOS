# NexoPOS 4.x Modules

## Quick Overview

Modules are detachable pieces of codes usually provided as a Zip file that extends or change the actual behavior of NexoPOS 4.x either by adding new features or by adjusting exiting features.
A module can be generated directly from the command line, and this should be the prefered way to generate a module with all the minimum required files. 

Every module to be recognized needs to have a valid XML file which is used as the definition file and a valid Entry point which is where NexoPOS 4.x starts the modules. 

## Generate A Module

From the CLI (php artisan), you'll start by using the command "make:module". The final command to create a module should be : 

```CLI
php artisan make:module
```

You'll be asked to provide :
- Your module name which is the human name
- Your module identifier (formely namespace) that is used internally
- Your module version (1.0 by default)
- Your module author (your name)
- Your module description (optional)

Once you're set you should have an overview of your provided information. You'll then be asked to approve or not.
