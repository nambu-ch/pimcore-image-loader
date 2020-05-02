<?php
/**
 * @var \Pimcore\Templating\PhpEngine       $view
 * @var \Pimcore\Templating\GlobalVariables $app
 * @var \Pimcore\Model\Asset\Image          $image
 * @var \Pimcore\Model\Document\Tag\Image   $imageBlock
 */

$twigExtension = new \ImageLoaderBundle\Service\ImageLoaderTwigExtensions();

if ($image instanceof \Pimcore\Model\Asset\Image) {
    echo $twigExtension->imageloaderFromAsset($image, [
        "sizeSelector"      => $view->sizeSelector,
        "isBackgroundImage" => $view->isBackgroundImage ?? false,
        "imageCssClass"     => $view->imageCssClass,
        "sizesOptions"      => $view->sizesOptions,
        "thumbnailNames"    => $view->thumbnailNames,
        "widths"            => $view->widths,
    ]);
} else if ($imageBlock instanceof \Pimcore\Model\Document\Tag\Image) {
    echo $twigExtension->imageloaderFromBlock($imageBlock, [
        "sizeSelector"      => $view->sizeSelector,
        "isBackgroundImage" => $view->isBackgroundImage ?? false,
        "imageCssClass"     => $view->imageCssClass,
        "sizesOptions"      => $view->sizesOptions,
        "thumbnailNames"    => $view->thumbnailNames,
        "widths"            => $view->widths,
    ]);
} else {
    echo 'Provide an "image" or an "imageBlock" to render the image from.';
}
