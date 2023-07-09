<?php

return [

    /*
     * Set true to connect over SSH
     */
    'useSsh' => env('REMOTE_USE_SSH', false),

    /*
     * Set the remote host either an IP address or URL
     */
    'host' => env('REMOTE_DATABASE_HOST', ''),

    /*
     * Set the SSH username
     */
    'sshUsername' => env('REMOTE_SSH_USERNAME', ''),

    /*
     * Set the SSH port number, defaults to 22 when no option provided
     */
    'sshPort' => env('REMOTE_SSH_PORT', '22'),

    /*
     * Database username
     */
    'username' => env('REMOTE_DATABASE_USERNAME', ''),

    /*
     * Database port
     */
    'port' => env('REMOTE_DATABASE_PORT', '3306'),

    /*
     * Set the database name
     */
    'database' => env('REMOTE_DATABASE_NAME', ''),

    /*
     * Set the database password
     */
    'password' => env('REMOTE_DATABASE_PASSWORD', ''),

    /*
     * provide a comma seperated list of tables to ignore, when set the tables specified will not be exported
     */
    'ignore' => env('REMOTE_DATABASE_IGNORE_TABLES', ''),

    /*
     * Sets if the exported SQL file will be imported into the current database connection
     */
    'importSqlFile' => env('REMOTE_IMPORT_FILE', 'true'),

    /*
     * Sets if the generated SQL file will be deleted after it has been imported.
     */
    'removeFileAfterImport' => env('REMOTE_REMOVE_FILE_AFTER_IMPORT', 'true'),

    /*
     * Sets the default name for SQL file if --filename is not provided
     */
    'defaultFileName' => env('REMOTE_DEFAULT_FILE_NAME', 'file.sql'),

    /*
     * Sets the target databse connection
     */
    'targetConnection' => env('LOCAL_TARGET_CONNECTION', 'mysql'),

    'mysqldumpSkipTzUtc' => env('REMOTE_MYSQLDUMP_SKIP_TZ_UTC', false),
];
