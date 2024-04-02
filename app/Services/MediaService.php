<?php

namespace App\Services;

use App\Classes\Hook;
use App\Models\Media;
use Exception;
use Gumlet\ImageResize;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MediaService
{
    /**
     * define sizes
     *
     * @var array
     */
    private $sizes = [
        'thumb' => [ 280, 181 ],
    ];

    private $mimeExtensions;

    /**
     * image extensions
     *
     * @var array<string>
     */
    private $imageExtensions = [ 'png', 'jpg', 'jpeg', 'gif' ];

    public function __construct( private DateService $dateService )
    {
        $this->mimeExtensions = config( 'medias.mimes' );
    }

    public function getMimes()
    {
        return Hook::filter( 'ns-media-mimes', $this->mimeExtensions );
    }

    public function getMimeExtensions()
    {
        return array_keys( $this->getMimes() );
    }

    /**
     * Return all mimes that can be
     * used as images.
     */
    public function getImageMimes(): Collection
    {
        return Hook::filter( 'ns-media-image-ext', collect( $this->mimeExtensions )
            ->filter( fn( $value, $key ) => in_array( $key, $this->imageExtensions ) ) );
    }

    /**
     * Upload a file
     *
     * @param object File
     * @return bool / media
     */
    public function upload( $file, $customName = null )
    {
        /**
         * getting file extension
         */
        $extension = strtolower( $file->getClientOriginalExtension() );

        if ( in_array( $extension, $this->getMimeExtensions() ) ) {
            $uploadedInfo = pathinfo( $file->getClientOriginalName() );
            $fileName = Str::slug( $uploadedInfo[ 'filename' ], '-' );
            $fileName = ( $customName == null ? $fileName : $customName );
            $fileName = $this->__preventDuplicate( $fileName );
            $fullFileName = $fileName . '.' . strtolower( $file->getClientOriginalExtension() );

            /**
             * let's get if an existing file
             * already exists. If that exists, let's adjust the file
             * fullname
             */
            $media = Media::where( 'name', $fullFileName )->first();

            if ( $media instanceof Media ) {
                $fileName = $fileName . Str::slug( $this->dateService->toDateTimeString() );
                $fullFileName = $fileName . '.' . strtolower( $file->getClientOriginalExtension() );
            }

            $year = $this->dateService->year;
            $month = sprintf( '%02d', $this->dateService->month );
            $folderPath = Hook::filter( 'ns-media-path', $year . DIRECTORY_SEPARATOR . $month . DIRECTORY_SEPARATOR );
            $indexPath = $folderPath . 'index.html';

            /**
             * If the storage folder hasn't been created
             * we'll create one and save an empty index within.
             */
            if ( ! Storage::disk( 'public' )->exists( $indexPath ) ) {
                Storage::disk( 'public' )->put( $indexPath, '' );
            }

            $filePath = Storage::disk( 'public' )->putFileAs(
                $folderPath,
                $file,
                $fullFileName
            );

            if ( in_array( $extension, $this->imageExtensions ) ) {
                /**
                 * Resizing the images
                 */
                $fullPath = storage_path( 'app' . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . $filePath );
                $realPathInfo = pathinfo( $fullPath );

                foreach ( $this->sizes as $resizeName => $size ) {
                    $image = new ImageResize( $fullPath );
                    $image->resizeToBestFit( $size[0], $size[1] );
                    $image->save( $realPathInfo[ 'dirname' ] . DIRECTORY_SEPARATOR . $fileName . '-' . $resizeName . '.' . $extension );
                }
            }

            $media = new Media;
            $media->name = $fileName;
            $media->extension = $extension;
            $media->slug = Hook::filter( 'ns-media-path', $year . '/' . $month . '/' . $fileName );
            $media->user_id = Auth::id();
            $media->save();

            return $this->getSizesUrls( $media );
        }

        return false;
    }

    /**
     * prevent duplicated
     *
     * @param string
     * @return string
     */
    public function __preventDuplicate( $filename )
    {
        $date = app()->make( DateService::class );
        $media = Media::where( 'name', $filename )
            ->first();

        if ( $media instanceof Media ) {
            return $filename . $date->micro;
        }

        return $filename;
    }

    /**
     * get image
     *
     * @param string file name
     * @param string size
     * @return mixed
     */
    public function get( $filename, $size = 'original' )
    {
        if ( in_array( $size, array_keys( $this->sizes ) ) ) {
            $file = Media::where( 'slug', $filename )->first();
        }

        return false;
    }

    /**
     * find media using the ID
     *
     * @param int
     * @return Media model
     */
    public function find( $id )
    {
        $file = Media::where( 'id', $id )->first();
        if ( $file instanceof Media ) {
            return $this->getSizesUrls( $file );
        }

        return false;
    }

    /**
     * Delete specific media by id
     *
     * @param int media id
     * @return json
     */
    public function deleteMedia( $id )
    {
        $media = Media::find( $id );

        if ( $media instanceof Media ) {
            $media = $this->getSizesUrls( $media );

            foreach ( $media->sizes as $name => $file ) {
                // original files doesn't have the slug original
                // so we'll keep that empty
                $name = $name == 'original' ? '' : '-' . $name;

                Storage::disk( 'public' )->delete( $media->slug . $name . '.' . $media->extension );
            }

            $media->delete();

            return [
                'status' => 'success',
                'message' => __( 'The media has been deleted' ),
            ];
        }

        return [
            'status' => 'error',
            'message' => __( 'Unable to find the media.' ),
        ];
    }

    /**
     * Load Medias
     *
     * @param media int
     * @return void
     */
    public function loadAjax()
    {
        $per_page = request()->query( 'per_page' ) ?? 20;
        $user_id = request()->query( 'user_id' );
        $search = request()->query( 'search' ) ?? null;

        $mediaQuery = Media::with( 'user' )
            ->orderBy( 'updated_at', 'desc' );

        /**
         * We'll only load the medias that has
         * been uploaded by the logged user.
         */
        if ( ! empty( $user_id ) ) {
            $mediaQuery->where( 'user_id', $user_id );
        }

        if ( ! empty( $search ) ) {
            $mediaQuery->where( 'name', 'like', "%$search%" );
        }

        $medias = $mediaQuery->paginate( $per_page );

        /**
         * populating the media
         */
        foreach ( $medias as &$media ) {
            $media = $this->getSizesUrls( $media );
        }

        return $medias;
    }

    /**
     * @private
     *
     * @param object media entry
     * @return Media
     */
    private function getSizesUrls( Media $media )
    {
        $media->sizes = new \stdClass;
        $media->sizes->{'original'} = Storage::disk( 'public' )->url( $media->slug . '.' . $media->extension );

        /**
         * provide others url if the media is an image
         */
        if ( in_array( $media->extension, $this->imageExtensions ) ) {
            foreach ( $this->sizes as $name => $sizes ) {
                $media->sizes->$name = Storage::disk( 'public' )->url( $media->slug . '-' . $name . '.' . $media->extension );
            }
        }

        return $media;
    }

    /**
     * get media path
     *
     * @param number media id
     * @return array
     */
    public function getMediaPath( $id, $size = '' )
    {
        $media = $this->find( $id );
        if ( $media instanceof Media ) {
            $file = Storage::disk( 'public' )->path( $media->slug . ( ! empty( $size ) ? '-' . $size : '' ) . '.' . $media->extension );

            if ( is_file( $file ) ) {
                return Storage::disk( 'public' )->download( $media->slug . ( ! empty( $size ) ? '-' . $size : '' ) . '.' . $media->extension );
            }

            throw new Exception( __( 'Unable to find the requested file.' ) );
        }
        throw new Exception( __( 'Unable to find the media entry' ) );
    }
}
