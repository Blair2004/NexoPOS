<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\DashboardController;
use App\Services\MediaService;
use Illuminate\Http\Request;

class MediasController extends DashboardController
{
    protected $mediaService;

    public function __construct(
        MediaService $mediaService
    )
    {
        parent::__construct();
        
        $this->mediaService     =   $mediaService;
    }

    public function showMedia()
    {
        return $this->view( 'pages.dashboard.medias.list', [
            'title'         =>  __( 'Manage Medias' ),
            'description'   =>  __( 'Upload and manage medias (photos).' )
        ]);
    }

    /**
     * perform
     * @param 
     * @return json
     */
    public function getMedias()
    {
        return $this->mediaService->loadAjax();
    }   
    
    /**
     * perform
     * @param 
     * @return json
     */
    public function deleteMedia()
    {

    }

    /**
     * perform
     * @param 
     * @return json
     */
    public function updateMedia()
    {

    }   

    public function bulkDeleteMedias( Request $request )
    {
        ns()->restrict( 'nexopos.delete.medias' );

        $result         =   [];

        foreach( $request->input( 'ids' ) as $id ) {
            $result[]   =   $this->mediaService->deleteMedia( $id );
        }

        return [
            'status'    =>  'success',
            'message'   =>  __( 'The operation was successful.' ),
            'data'      =>  compact( 'result' )
        ];
    }

    public function uploadMedias( Request $request )
    {
        return $this->mediaService->upload( $request->file( 'file' ) );
    }
}
