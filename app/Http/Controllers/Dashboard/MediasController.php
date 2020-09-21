<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\DashboardController;
use App\Services\MediaService;
use Illuminate\Http\Request;

class MediasController extends DashboardController
{
    protected $mediaSerice;

    public function __construct(
        MediaService $mediaService
    )
    {
        parent::__construct();
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

    public function uploadMedias( Request $request )
    {
        return $this->mediaSerice->upload( $request->file( 'file' ) );
    }
}
