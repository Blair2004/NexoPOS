<?php
namespace Tendoo\Core\Services;

use Carbon\Carbon;
use Tendoo\Core\Services\DateService;
use Tendoo\Core\Models\Media;
use Tendoo\Core\Exceptions\NotFoundException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Gumlet\ImageResize;

class MediaService
{
    /**
     * define sizes
     * @var array
     */
    private $sizes      =   [
        'thumb'     =>  [ 280, 181 ],
    ];

    /**
     * Supported File Extensions
     * @var array<String>
     */
    private $extensions    =   [];

    /**
     * image extensions
     * @var array<string>
     */
    private $images_extensions  =   [ 'png', 'jpg', 'jpeg', 'gif' ];

    public function __construct( $data ) 
    {
        extract( $data );
        $this->extensions   =   $extensions;
        $this->date         =   app()->make( DateService::class );
    }

    /**
     * Upload a file
     * @param object File
     * @return boolean / media
     */
    public function upload( $file, $customName = null )
    {
        /**
         * getting file extension
         */
        // $extension  =   $file->extension();
        $extension  =   strtolower( $file->getClientOriginalExtension() );

        if ( in_array( $extension, $this->extensions ) ) {

            $uploadedInfo   =   pathinfo( $file->getClientOriginalName() );
            $fileName       =   Str::slug( $uploadedInfo[ 'filename' ], '-' );
            $fileName       =   ( $customName == null ? $fileName : $customName );
            $fileName       =   $this->__preventDuplicate( $fileName );
            $fullFileName   =   $fileName . '.' . strtolower( $file->getClientOriginalExtension() );

            /**
             * let's get if an existing file 
             * already exists. If that exists, let's adjust the file
             * fullname
             */
            $media          =   Media::where( 'name', $fullFileName )->first();

            if ( $media instanceof Medias ) {
                $fileName       =   $fileName . str_slug( $this->date->toDateTimeString() );    
                $fullFileName   =   $fileName . '.' . strtolower( $file->getClientOriginalExtension() );
            }

            $year           =   $this->date->year;
            $month          =   sprintf( "%02d", $this->date->month );
            $folderPath     =   $year . DIRECTORY_SEPARATOR . $month . DIRECTORY_SEPARATOR;
            
            $filePath       =   Storage::disk( 'public' )->putFileAs( 
                '', 
                $file,
                $folderPath . $fullFileName
            );

            if ( in_array( $extension, $this->images_extensions ) ) {
                /**
                 * Resizing the images
                 */
                $fullPath           =   storage_path( 'app' . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . $filePath );
                $realPathInfo       =   pathinfo( $fullPath );
    
                foreach( $this->sizes as $resizeName => $size ) {
                    $image      =   new ImageResize( $fullPath );
                    $image->resizeToBestFit( $size[0], $size[1] );
                    $image->save( $realPathInfo[ 'dirname' ] . DIRECTORY_SEPARATOR . $fileName . '-' . $resizeName . '.' . $extension );
                }
            }

            $media              =   new Media;
            $media->name        =   $fileName;
            $media->extension   =   $extension;
            $media->slug        =   $year . '/' . $month . '/' . $fileName;
            $media->user_id     =   Auth::id();
            $media->save();

            return $this->getSizesUrls( $media );
        }
        
        return false;
    }

    /**
     * prevent duplicated
     * @param string
     * @return string
     */
    public function __preventDuplicate( $filename )
    {
        $date   =   app()->make( DateService::class );
        $media  =   Media::where( 'name', $filename )
            ->first();

        if ( $media instanceof Media ) {
            return $filename . $date->micro;
        }

        return $filename;
    }

    /**
     * get image
     * @param string file name
     * @param string size
     * @return mixed
     */
    public function get( $filename, $size = 'original' )
    {
        if ( in_array( $size, array_keys( $this->sizes ) ) ) {
            $file   =   Media::where( 'slug', $filename )->first();
        }
        return false;
    }

    /**
     * find media using the ID
     * @param int 
     * @return Media model
     */
    public function find( $id ) 
    {
        $file   =   Media::where( 'id', $id )->first();
        if ( $file instanceof Media ) {
            return $this->getSizesUrls( $file );
        }
        return false;
    }

    /**
     * Delete specific media by id
     * @param int media id
     * @return json
     */
    public function deleteMedia( $id ) 
    {
        $media  =   Media::find( $id );

        if ( $media instanceof Media ) {
            $media  =   $this->getSizesUrls( $media );
            
            foreach( $media->sizes as $name => $file ) {
                // original files doesn't have the slug original
                // so we'll keep that empty
                $name = $name == 'original' ? '' : '-' . $name;
    
                Storage::disk( 'public' )->delete( $media->slug . $name . '.' . $media->extension );
            }
    
            $media->delete();
    
            return [
                'status'    =>  'success',
                'message'   =>  __( 'The media has been deleted' )
            ];
        }

        return [
            'status'    =>  'failed',
            'message'   =>  __( 'Unable to find the media.' )
        ];
    }

    /**
     * Load Medias
     * @param media int
     * @return void
     */
    public function loadAjax()
    {
        $per_page   =   request()->query( 'per_page' ) ?? 50;
        $medias     =   Media::orderBy( 'updated_at', 'desc' )->paginate($per_page);
        
        /**
         * populating the media
         */
        foreach( $medias as &$media ) {
            $media       =   $this->getSizesUrls( $media );
        }

        return $medias;
    }

    /**
     * @private
     * @param object media entry
     * @return array
     */
    private function getSizesUrls( Media $media ) 
    {
        $media->sizes                   =   new \stdClass;
        $media->sizes->{'original'}     =   Storage::disk( 'public' )->url( $media->slug . '.' . $media->extension );

        /**
         * provide others url if the media is an image
         */
        if ( in_array( $media->extension, $this->images_extensions ) ) {
            foreach( $this->sizes as $name => $sizes ) {
                $media->sizes->$name    =   Storage::disk( 'public' )->url( $media->slug . '-' . $name . '.' . $media->extension );        
            }
        }

        return $media;
    }

    /**
     * get media path
     * @param number media id
     * @return array
     */
    public function getMediaPath( $id, $size = '' )
    {
        $media  =   $this->find( $id );
        if ( $media instanceof Media ) {
            $file   =   Storage::disk( 'public' )->path( $media->slug . ( ! empty( $size ) ? '-' . $size : '' ) . '.' . $media->extension );
            
            if ( is_file( $file ) ) {
                return Storage::disk( 'public' )->download( $media->slug . ( ! empty( $size ) ? '-' . $size : '' ) . '.' . $media->extension  );
            }

            throw new NotFoundException([
                'status'    =>  'failed',
                'message'   =>  __( 'Unable to find the requested file.' )
            ]);
        }
        throw new NotFoundException([
            'status'    =>  'failed',
            'message'   =>  __( 'Unable to find the media entry' )
        ]);
    }
}