<?php

namespace App\Traits;

trait NsDependable
{
    public function getDeclaredDependencies()
    {
        return $this->isDependencyFor;
    }
}
