<?php

namespace App\Console\Commands;

use App\Services\ModulesService;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ExtractTranslation extends Command
{
    private $modulesService;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ns:translate {module?} {--extract} {--lang=en} {--symlink}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Will perform various operation regarding translation for NexoPOS and it\'s modules';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(
        ModulesService $modulesService
    ) {
        parent::__construct();

        $this->modulesService = $modulesService;
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        if ( $this->option( 'extract' ) ) {
            $this->extracting();
        } elseif ( $this->option( 'symlink' ) ) {
            $this->createSymlink();
        }
    }

    /**
     * Create symbolic link
     */
    private function createSymLink()
    {
        if ( ! \windows_os() ) {
            $link = @\symlink( base_path( 'lang' ), public_path( '/lang' ) );
        } else {
            $mode = 'J';
            $link = public_path( 'lang' );
            $target = base_path( 'lang' );
            $link = exec( "mklink /{$mode} \"{$link}\" \"{$target}\"" );
        }

        return $this->info( 'Language Symbolic Link has been created !' );
    }

    /**
     * That will extrac tthe language string
     * for a specific language code
     *
     * @param  string     $lang
     * @param  array      $module
     * @param  Collection $files
     * @return void
     */
    private function extractForModuleLanguage( $lang, $module, $files )
    {
        $filePath = Str::finish( $module[ 'lang-relativePath' ], DIRECTORY_SEPARATOR ) . $lang . '.json';
        $finalArray = $this->extractLocalization( $files->flatten() );
        $finalArray = $this->flushTranslation( $finalArray, $filePath );

        Storage::disk( 'ns' )->put( $filePath, json_encode( $finalArray ) );

        $this->newLine();
        $this->info( sprintf( __( 'Localization for %s extracted to %s' ), config( 'nexopos.languages' )[ $lang ], $filePath ) );
    }

    private function extracting()
    {
        $this->info( 'Extracting...' );

        if ( $this->argument( 'module' ) ) {
            $module = $this->modulesService->get( $this->argument( 'module' ) );

            if ( ! empty( $module ) ) {
                $directories = Storage::disk( 'ns' )->directories( $module[ 'relativePath' ] );
                $files = collect( [] );

                foreach ( $directories as $directory ) {
                    if ( ! in_array( basename( $directory ), [ 'node_modules', 'vendor', 'Public', '.git' ] ) ) {
                        $files->push( Storage::disk( 'ns' )->allFiles( $directory ) );
                    }
                }

                Storage::disk( 'ns' )->put( $module[ 'relativePath' ] . DIRECTORY_SEPARATOR . 'Lang' . DIRECTORY_SEPARATOR . 'index.html', '' );

                /**
                 * We'll loop all the languages that are available
                 */
                if ( $this->option( 'lang' ) === 'all' ) {
                    foreach ( config( 'nexopos.languages' ) as $lang => $humanName ) {
                        $this->extractForModuleLanguage( $lang, $module, $files );
                    }
                } else {
                    $this->extractForModuleLanguage( $this->option( 'lang' ), $module, $files );
                }

                return $this->info( sprintf( __( 'Translation process is complete for the module %s !' ), $module[ 'name' ] ) );
            } else {
                return $this->error( __( 'Unable to find the requested module.' ) );
            }
        } else {
            $files = array_merge(
                Storage::disk( 'ns' )->allFiles( 'app' ),
                Storage::disk( 'ns' )->allFiles( 'resources' ),
            );

            if ( $this->option( 'lang' ) === 'all' ) {
                foreach ( config( 'nexopos.languages' ) as $lang => $humanName ) {
                    $this->extractLanguageForSystem( $lang, $files );
                }
                $this->info( 'Translation process is complete !' );
            } else {
                return $this->extractLanguageForSystem( $this->option( 'lang' ), $files );
            }
        }
    }

    /**
     * Will perform string extraction for
     * the system files
     *
     * @param  string $lang
     * @param  array  $files
     * @return void
     */
    private function extractLanguageForSystem( $lang, $files )
    {
        $filePath = 'lang/' . $lang . '.json';
        $finalArray = $this->extractLocalization( $files );
        $finalArray = $this->flushTranslation( $finalArray, $filePath );

        Storage::disk( 'ns' )->put( 'lang/' . $lang . '.json', json_encode( $finalArray ) );

        $this->newLine();
        $this->info( 'Extraction complete for language : ' . config( 'nexopos.languages' )[ $lang ] );
    }

    /**
     * Will merge translation files
     * by deleting old string that aren't referenced
     *
     * @param  array  $newTranslation
     * @param  string $filePath
     * @return array  $updatedTranslation
     */
    private function flushTranslation( $newTranslation, $filePath )
    {
        $existingTranslation = [];

        if ( Storage::disk( 'ns' )->exists( $filePath ) ) {
            $existingTranslation = json_decode( Storage::disk( 'ns' )->get( $filePath ), true );
        }

        if ( ! empty( $existingTranslation ) ) {
            /**
             * delete all keys that doesn't exists
             */
            $purgedTranslation = collect( $existingTranslation )
                ->filter( function ( $translation, $key ) use ( $newTranslation ) {
                    return in_array( $key, array_keys( $newTranslation ) );
                } );

            /**
             * pull new keys
             */
            $newKeys = collect( $newTranslation )->filter( function ( $translation, $key ) use ( $existingTranslation ) {
                return ! in_array( $key, array_keys( $existingTranslation ) );
            } );

            return array_merge( $purgedTranslation->toArray(), $newKeys->toArray() );
        }

        return $newTranslation;
    }

    private function extractLocalization( $files )
    {
        $supportedExtensions = [ 'vue', 'php', 'ts', 'js' ];

        $filtered = collect( $files )->filter( function ( $file ) use ( $supportedExtensions ) {
            $info = pathinfo( $file );

            return in_array( $info[ 'extension' ], $supportedExtensions );
        } );

        $exportable = [];

        /**
         * we'll extract all the string that can be translated
         * and save them within an array.
         */
        $this->withProgressBar( $filtered, function ( $file ) use ( &$exportable ) {
            $fileContent = Storage::disk( 'ns' )->get( $file );
            preg_match_all( '/__[m]?\(\s*(?(?=[\'"`](?:[\s\S]*?)[\'"`](?:,\s*(?:[^)]*))?)[\'"`]([\s\S]*?)[\'"`](?:,\s*(?:[^)]*))?|)\s*\)/', $fileContent, $output_array );

            if ( isset( $output_array[1] ) ) {
                foreach ( $output_array[1] as $string ) {
                    $exportable[ $string ] = compact( 'file', 'string' );
                }
            }
        } );

        return collect( $exportable )->mapWithKeys( function ( $exportable ) {
            return [ $exportable[ 'string' ] => $exportable[ 'string' ] ];
        } )->toArray();
    }
}
