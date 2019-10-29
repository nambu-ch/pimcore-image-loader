<?php
namespace ImageLoaderBundle;

use Pimcore\Extension\Bundle\AbstractPimcoreBundle;
use Pimcore\Extension\Bundle\Traits\PackageVersionTrait;

class ImageLoaderBundle extends AbstractPimcoreBundle {

    use PackageVersionTrait;

    const PACKAGE_NAME = 'nambu-ch/pimcore-image-loader';

    protected function getComposerPackageName(): string
    {
        return self::PACKAGE_NAME;
    }

}
