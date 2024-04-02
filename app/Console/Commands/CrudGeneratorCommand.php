<?php

namespace App\Console\Commands;

use App\Services\ModulesService;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CrudGeneratorCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:crud {module?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate a crud resource for a module model';

    /**
     * Crud Details
     */
    private $crudDetails = [];

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $moduleNamespace = $this->argument( 'module' );

        if ( ! empty( $moduleNamespace ) ) {
            $modulesService = app()->make( ModulesService::class );
            $module = $modulesService->get( $moduleNamespace );

            if ( empty( $module ) ) {
                throw new Exception( sprintf( __( 'Unable to find a module having the identifier/namespace "%s"' ), $moduleNamespace ) );
            }
        }

        return $this->askResourceName();
    }

    /**
     * Resource Name
     *
     * @return void
     */
    public function askResourceName()
    {
        $name = $this->ask( __( 'What is the CRUD single resource name ? [Q] to quit.' ) );
        if ( $name !== 'Q' && ! empty( $name ) ) {
            $this->crudDetails[ 'resource_name' ] = $name;

            return $this->askTableName();
        } elseif ( $name == 'Q' ) {
            return;
        }
        $this->error( __( 'Please provide a valid value' ) );

        return $this->askResourceName();
    }

    /**
     * Table Name
     *
     * @return void
     */
    public function askTableName()
    {
        $name = $this->ask( __( 'Which table name should be used ? [Q] to quit.' ) );
        if ( $name !== 'Q' && ! empty( $name ) ) {
            $this->crudDetails[ 'table_name' ] = $name;

            return $this->askMainRoute();
        } elseif ( $name == 'Q' ) {
            return;
        }
        $this->error( __( 'Please provide a valid value' ) );

        return $this->askTableName();
    }

    /**
     * Crud Name
     *
     * @return void
     */
    public function askMainRoute()
    {
        $name = $this->ask( __( 'What slug should be used ? [Q] to quit.' ) );
        if ( $name !== 'Q' && ! empty( $name ) ) {
            $this->crudDetails[ 'route_name' ] = $name;

            return $this->askNamespace();
        } elseif ( $name == 'Q' ) {
            return;
        }
        $this->error( __( 'Please provide a valid value' ) );

        return $this->askMainRoute();
    }

    /**
     * Crud Name
     *
     * @return void
     */
    public function askNamespace()
    {
        $name = $this->ask( __( 'What is the namespace of the CRUD Resource. eg: system.users ? [Q] to quit.' ) );
        if ( $name !== 'Q' && ! empty( $name ) ) {
            $this->crudDetails[ 'namespace' ] = $name;

            return $this->askFullModelName();
        } elseif ( $name == 'Q' ) {
            return;
        }
        $this->error( __( 'Please provide a valid value.' ) );

        return $this->askNamespace();
    }

    /**
     * Crud Name
     *
     * @return void
     */
    public function askFullModelName()
    {
        $name = $this->ask( __( 'What is the full model name. eg: App\Models\Order ? [Q] to quit.' ) );
        if ( $name !== 'Q' && ! empty( $name ) ) {
            $this->crudDetails[ 'model_name' ] = $name;

            return $this->askRelation();
        } elseif ( $name == 'Q' ) {
            return;
        }
        $this->error( __( 'Please provide a valid value' ) );

        return $this->askFullModelName();
    }

    /**
     * Crud Name
     *
     * @return void
     */
    public function askRelation( $fresh = true )
    {
        if ( $fresh ) {
            $message = __( 'If your CRUD resource has a relation, mention it as follow "foreign_table, foreign_key, local_key" ? [S] to skip, [Q] to quit.' );
        } else {
            $message = __( 'Add a new relation ? Mention it as follow "foreign_table, foreign_key, local_key" ? [S] to skip, [Q] to quit.' );
        }

        $name = $this->ask( $message );
        if ( $name !== 'Q' && $name != 'S' && ! empty( $name ) ) {
            if ( @$this->crudDetails[ 'relations' ] == null ) {
                $this->crudDetails[ 'relations' ] = [];
            }
            $parameters = explode( ',', $name );

            if ( count( $parameters ) != 3 ) {
                $this->error( __( 'Not enough parameters provided for the relation.' ) );

                return $this->askRelation( false );
            }

            $this->crudDetails[ 'relations' ][] = [
                trim( $parameters[0] ),
                trim( $parameters[0] ) . '.' . trim( $parameters[2] ),
                $this->crudDetails[ 'table_name' ] . '.' . trim( $parameters[1] ),
            ];

            return $this->askRelation( false );
        } elseif ( $name === 'S' ) {
            return $this->askFillable();
        } elseif ( $name == 'Q' ) {
            return;
        }
        $this->error( __( 'Please provide a valid value' ) );

        return $this->askRelation();
    }

    /**
     * Crud Name
     *
     * @return void
     */
    public function askFillable()
    {
        $name = $this->ask( __( 'What are the fillable column on the table: eg: username, email, password ? [S] to skip, [Q] to quit.' ) );
        if ( $name !== 'Q' && ! empty( $name ) && $name != 'S' ) {
            $this->crudDetails[ 'fillable' ] = $name;

            return $this->generateCrud();
        } elseif ( $name == 'S' ) {
            $this->crudDetails[ 'fillable' ] = '';

            return $this->generateCrud();
        } elseif ( $name == 'Q' ) {
            return;
        }
        $this->error( __( 'Please provide a valid value' ) );

        return $this->askFillable();
    }

    /**
     * Crud Name
     *
     * @return void
     */
    public function generateCrud()
    {
        $moduleNamespace = $this->argument( 'module' );

        if ( ! empty( $moduleNamespace ) ) {
            $modulesService = app()->make( ModulesService::class );
            $module = $modulesService->get( $moduleNamespace );

            if ( $module ) {
                $fileName = $module[ 'namespace' ] . DIRECTORY_SEPARATOR . 'Crud' . DIRECTORY_SEPARATOR . ucwords( Str::camel( $this->crudDetails[ 'resource_name' ] ) ) . 'Crud.php';
                Storage::disk( 'ns-modules' )->put(
                    $fileName,
                    view( 'generate.crud', array_merge(
                        $this->crudDetails, [
                            'module' => $module,
                        ]
                    ) )
                );

                return $this->info( sprintf(
                    __( 'The CRUD resource "%s" for the module "%s" has been generated at "%s"' ),
                    $this->crudDetails[ 'resource_name' ],
                    $module[ 'name' ],
                    $fileName
                ) );
            }
        } else {
            $fileName = 'app' . DIRECTORY_SEPARATOR . 'Crud' . DIRECTORY_SEPARATOR . ucwords( Str::camel( $this->crudDetails[ 'resource_name' ] ) ) . 'Crud.php';

            Storage::disk( 'ns' )->put(
                $fileName,
                view( 'generate.crud', $this->crudDetails )
            );

            return $this->info( sprintf(
                __( 'The CRUD resource "%s" has been generated at %s' ),
                $this->crudDetails[ 'resource_name' ],
                $fileName
            ) );
        }

        return $this->error( __( 'An unexpected error has occurred.' ) );
    }
}
