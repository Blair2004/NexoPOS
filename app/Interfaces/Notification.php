<?php
namespace App\Interfaces;

interface Notification
{
    public $source;
    public $url;
    public $title;
    public $description;
    public $dismissable;
    public $identifier;
}