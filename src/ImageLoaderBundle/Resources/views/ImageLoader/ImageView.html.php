<?php
/**
 * @var \Pimcore\Templating\PhpEngine                                $view
 * @var \Pimcore\Templating\GlobalVariables                          $app
 * @var \Pimcore\Model\Asset\Image|\Pimcore\Model\Document\Tag\Image $image
 * @deprecated
 * @var \Pimcore\Model\Document\Tag\Image                            $imageBlock Legacy Property
 */

$twigExtension = new \ImageLoaderBundle\Service\ImageLoaderTwigExtensions();
if (!empty($imageBlock)) $image = $imageBlock;

if ($image instanceof \Pimcore\Model\Asset\Image) {
    echo $twigExtension->imageloaderFromAsset($image, [
        "sizeSelector"      => $view->sizeSelector,
        "isBackgroundImage" => $view->isBackgroundImage ?? false,
        "imageCssClass"     => $view->imageCssClass,
        "sizesOptions"      => $view->sizesOptions,
        "thumbnailNames"    => $view->thumbnailNames,
        "widths"            => $view->widths,
    ]);
} else if ($image instanceof \Pimcore\Model\Document\Tag\Image) {
    echo $twigExtension->imageloaderFromBlock($image, [
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
