<?php

namespace App\Services;

class Schema
{
    /**
     * render entry
     *
     * @param array of schema structure
     * @return void
     */
    public function render( $schemas )
    {
        foreach ( $schemas as $name => $type ) {
            if ( in_array( $type, [
                'bigIncrements',
                'bigInteger', 'binary',
                'boolean', 'char', 'date',
                'datetime', 'decimal', 'double',
                'float', 'increments', 'integer', 'json',
                'jsonb', 'longText', 'mediumInteger', 'mediumText',
                'morphs', 'nullableTimestamps', 'smallInteger', 'tinyInteger',
                'string', 'text', 'time', 'timestamp',
            ] ) ) {
                echo "\t\t\t\$table->{$type}( '{$name}' );\n";
            } elseif ( in_array( $type, [
                'enum', 'softDeletes', 'timestamps', 'rememberToken', 'unsigned',
            ] ) ) {
            }
        }
    }

    /**
     * Render Schema
     *
     * @param array
     * @return void
     */
    public function renderSchema( $data )
    {
        extract( $data );

        if ( isset( $table ) && ! empty( $table ) ) {
            echo "if ( ! Schema::hasTable( '{$table}' ) ) {\n";
            echo "\t\t\tSchema::create( '{$table}', function (Blueprint \$table) {\n";
            echo "\t\t\t\t\$table->increments('id');\n";
            if ( @$schema ) {
                $this->render( $schema );
            }
            echo "\t\t\t\t\$table->timestamps();\n";
            echo "\t\t\t});\n";
            echo "\t\t}";
        } else {
            echo "// Add the schema here !\n";
        }
    }

    /**
     * Render Drop
     *
     * @param array of schema details
     * @return string
     */
    public function renderDrop( $details )
    {
        extract( $details );

        if ( isset( $table ) && ! empty( $table ) ) {
            echo "Schema::dropIfExists( '{$table}' );\n";
        } else {
            echo "// drop tables here\n";
        }
    }
}
