 ---
applyTo: '**'
---
NexoPOS is a point of sale system built on top of Laravel. it uses Vue.js (3) and Tailwind CSS. This document describes how the applications is structured and works.

## Application Structure
NexoPOS is structured into several key directories:
- `app/`: Contains the core application logic, including controllers, models, and services.
- `bootstrap/`: Contains the application bootstrap files, including the autoloader and application initialization.
- `config/`: Contains configuration files for the application.
- `database/`: Contains database migrations, seeders, and factories.
- `lang/`: Contains language files for localization.
- `modules/`: Contains the modules that extend the functionality of NexoPOS.
- `resources/`: Contains the frontend assets, including Vue components, Tailwind CSS styles, and language files.
- `routes/`: Contains the route definitions for the application.
- `database/`: Contains the database migrations, seeders, and factories.
- `config/`: Contains the configuration files for the application.
- `public/`: Contains the publicly accessible assets, such as images, JavaScript, and CSS files.
- `storage/`: Contains logs, cache, and other temporary files.
- `tests/`: Contains the test cases for the application.
- `vendor/`: Contains the third-party packages and dependencies managed by Composer.

### "app" Directory
The app directory is where the core logic of NexoPOS resides. It includes:
- **BulkEditor**: Which keeps configuration for the Bulk Editor module.
- **Casts**: Contains custom Eloquent casts used throughout the application.
- **Console**: Contains Artisan commands for the application.
- **Crud**: Contains CRUD classes for the application.
- **Enums**: Contains enumerations used in the application.
- **Events**: Contains event classes for the application.
- **Facades**: Contains facades for the application.
- **Fields**: Contains field classes used the application.
- **Forms**: Contains form classes used in the application.
- **Http**: Contains controllers, middleware, and requests for the application.
- **Jobs**: Contains scheduled jobs for the application.
- **Listeners**: Contains event listeners for the application.
- **Mail**: Contains mail classes for the application.
- **Models**: Contains Eloquent models for the application.
- **Observers**: Contains model observers for the application.
- **Providers**: Contains service providers for the application.
- **Repositories**: Contains repository classes for the application.
- **Services**: Contains service classes for the application.
- **Traits**: Contains reusable traits used throughout the application.
- **View**: Contains view composers and other view-related classes.
- **Widgets**: Contains widget classes used in the application.

## "modules" Directory
This is the location where module are installed. The name of a folder is part of the namespace used to resolve
classes automatically using PSR-4 autoloading. Each module can have its own structure, but typically it includes:

- **Classes**: Contains controllers for the module.
- **Console**: Contains Eloquent models for the module.
- **Crud**: Contains CRUD classes for the module.
- **Events**: Contains enumerations used in the module.
- **Fields**: Contains field classes used in the module.
- **Filters**: Contains filter classes used in the module.
- **Hook**: Contains hook classes used in the module.
- **Http**: Contains controllers, middleware, and requests for the module.
- **Jobs**: Contains scheduled jobs for the module.
- **Listeners**: Contains event listeners for the module.
- **Mail**: Contains mail classes for the module.
- **Migrations**: Contains database migrations for the module.
- **Models**: Contains Eloquent models for the module.
- **Providers**: Contains service providers for the module.
- **Public**: Contains publicly accessible assets for the module.
- **Resources**: Contains frontend assets, such as Vue components and Tailwind CSS styles for the module.
- **Routes**: Contains route definitions for the module.
- **Scopes**: Contains Eloquent scopes for the module.
- **Services**: Contains service classes for the module.
- **Settings**: Contains settings classes for the module.
- **Widgets**: Contains widget classes used in the module.

## "database" directory
The database directory contains the database-related files for NexoPOS. It includes:

- **migrations**: Contains database migration files that define the structure of the database tables.
- **seeders**: Contains database seeders that populate the database with initial data.
- **factories**: Contains model factories that define how to generate fake data for testing purposes.
- **json**: we might store there any demo json file. that can be used for seending.
- **permission**: Contains permission files that define the permissions for the application.

### Regarding "migrations" directory
That folder has 3 subfolders:
 
- **core**: Contains the core migrations that define the basic structure of the NexoPOS database.
- **create**: Contains migrations that create new tables in the database.
- **update**: Contains migrations that update existing tables in the database.

At the root of the directory, we might also have some laravel (or laravel packages) migration that are necessary to be executed.

## Additional Instructions
.github/instructions/nexopos-asidemenu.instructions.md
.github/instructions/nexopos-blade-layouts.instructions.md
.github/instructions/nexopos-crud.instructions.md
.github/instructions/nexopos-forminput.instructions.md
.github/instructions/nexopos-httpclient.instructions.md
.github/instructions/nexopos-localization.instructions.md
.github/instructions/nexopos-middleware.instructions.md
.github/instructions/nexopos-migrations.instructions.md
.github/instructions/nexopos-modules.instructions.md
.github/instructions/nexopos-permissions.instructions.md
.github/instructions/nexopos-roles-permissions.instructions.md
.github/instructions/nexopos-tabs.instructions.md
.github/instructions/nexopos-widgets.instructions.md
