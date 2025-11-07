<?php

namespace TerpDev\CubeWikiPackage\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \TerpDev\CubeWikiPackage\CubeWikiPackage
 */
class CubeWikiPackage extends Facade
{
    protected static function getFacadeAccessor()
    {
        return \TerpDev\CubeWikiPackage\CubeWikiPackage::class;
    }
}
