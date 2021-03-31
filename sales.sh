#!/bin/bash
php artisan env:set NS_MODULES_MANAGEMENT_DISABLED --v=false &&
php artisan config:clear &&
php artisan modules:enable NsDemo &&
php artisan modules:enable NsBulkImporter &&
php artisan env:set NS_MODULES_MANAGEMENT_DISABLED --v=true &&
php artisan config:clear &&
php artisan ns:reset &&
php artisan db:seed --class=DefaultSeeder &&
php artisan queue:restart &&
php artisan storage:link &&
php artisan ns:bulkimport /storage/app/products.csv --email=contact@nexopos.com --config=/storage/app/import-config.json
vendor/bin/phpunit --config=phpunit.sales.xml