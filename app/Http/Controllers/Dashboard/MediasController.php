<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\DashboardController;
use Illuminate\Http\Request;

class MediasController extends DashboardController
{
    public function __construct()
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

}
