<?php

namespace App\Classes;

use Exception;

class XMLParser
{
    private $xmlFilePath;

    private $xmlObject;

    public function __construct( string $xmlFilePath )
    {
        $this->xmlFilePath = $xmlFilePath;

        $this->loadXML();
    }

    private function loadXML()
    {
        if ( ! file_exists( $this->xmlFilePath ) ) {
            throw new Exception( 'File not found: ' . $this->xmlFilePath );
        }

        libxml_use_internal_errors( true );
        $this->xmlObject = simplexml_load_file( $this->xmlFilePath );
        if ( $this->xmlObject === false ) {
            $errors = libxml_get_errors();
            libxml_clear_errors();
            $errorMessages = [];
            foreach ( $errors as $error ) {
                $errorMessages[] = $error->message;
            }
            throw new Exception( 'Failed to load XML: ' . implode( ', ', $errorMessages ) );
        }
    }

    public function getXMLObject()
    {
        return $this->xmlObject;
    }

    // Example method to get data by tag name
    public function getDataByTagName( string $tagName )
    {
        if ( isset( $this->xmlObject->{$tagName} ) ) {
            return $this->xmlObject->{$tagName};
        } else {
            throw new Exception( 'Tag not found: ' . $tagName );
        }
    }
}
