<?php
/**
 * @var \Pimcore\Templating\PhpEngine       $view
 * @var \Pimcore\Templating\GlobalVariables $app
 */

//$image = $view->image;
//$imageBlock = $view->imageBlock;
//$sizeSelector = $view->sizeSelector;
//$enableImageloader = $view->enableImageloader ?? false;
//$isBackgroundImage = $view->isBackgroundImage ?? false;
//$imageCssClass = $view->imageCssClass;
//$enableLazyload = $view->enableLazyload ?? false;
//$thumbnailNames = $view->thumbnailNames ?? null;
//$hasImageBlock = false;
//$sizesOptions = $view->sizesOptions;

$twigExtension = new \ImageLoaderBundle\Service\ImageLoaderTwigExtensions();
if (!empty($image)) {
    echo $twigExtension->imageloaderFromAsset($image, [
        "sizeSelector" => $view->sizeSelector,
        "isBackgroundImage" => $view->isBackgroundImage ?? false,
        "imageCssClass" => $view->imageCssClass,
        "sizesOptions" => $view->sizesOptions,
        "thumbnailNames" => $view->thumbnailNames,
        "widths" => $view->widths,
    ]);
}
if (!empty($imageBlock)) {
    echo $twigExtension->imageloaderFromBlock($imageBlock, [
        "sizeSelector" => $view->sizeSelector,
        "isBackgroundImage" => $view->isBackgroundImage ?? false,
        "imageCssClass" => $view->imageCssClass,
        "sizesOptions" => $view->sizesOptions,
        "thumbnailNames" => $view->thumbnailNames,
        "widths" => $view->widths,
    ]);
}

//if ($image instanceof Pimcore\Model\Document\Tag\Image) {
//    $imageBlock = $image;
//    $image = $imageBlock->getImage();
//    $hasImageBlock = true;
//} else if ($imageBlock instanceof Pimcore\Model\Document\Tag\Image) {
//    $image = $imageBlock->getImage();
//    $hotspots = $imageBlock->getHotspots();
//    $hasImageBlock = true;
//}
//
//if (!function_exists("getHotspotLinks")) {
//    function getHotspotLinks($imageBlock, $hotspots) {
//        foreach ($hotspots as $area) {
//            if (count($area["data"]) != 1) {
//                continue;
//            }
//            //
//            if ($imageBlock->getCropTop() != null) {
//                // @todo: works only if the whole image is displayed
//                $area["top"] = ($area["top"] - $imageBlock->getCropTop());
//                $area["left"] = ($area["left"] - $imageBlock->getCropLeft());
//            }
//            $linkEl = null;
//            if ($area["data"][0]["value"] instanceof Pimcore\Model\Document\Page) {
//                /** @var Pimcore\Model\Document\Page $linkEl */
//                $linkEl = $area["data"][0]["value"];
//            } elseif ($area["data"][0]["value"] instanceof Pimcore\Model\Asset\Image) {
//                /** @var Pimcore\Model\Asset\Image $image */
//                $linkEl = $area["data"][0]["value"];
//            } elseif ($area["data"][0]["value"] instanceof Pimcore\Model\Asset) {
//                /** @var Pimcore\Model\Asset\Image $image */
//                $linkEl = $area["data"][0]["value"];
//            }
//            if ($linkEl != null) {
//                echo '<a style="position:absolute;display:inline-block;';
//                if (\Pimcore::inDebugMode()) {
//                    echo 'background-color:rgba(255,255,255,0.4);';
//                }
//                echo 'top:'.($area["top"]).'%;';
//                echo 'left:'.($area["left"]).'%;';
//                echo 'width:'.($area["width"]).'%;';
//                echo 'height:'.($area["height"]).'%;" href="'.$linkEl->getFullPath().'" data-href="'.$linkEl->getFullPath().'"></a>';
//            }
//        }
//    }
//}
//
//if (!function_exists("getThumbnailConfig")) {
//    function getThumbnailConfig(array $baseConfig, $width) {
//        $cfg = array_merge($baseConfig, ["width" => $width]);
//        if (array_key_exists("height", $cfg)) {
//            $cfg["height"] = $width;
//        }
//        return $cfg;
//    }
//}
//
//$options = array();
//
//if ($image instanceof Pimcore\Model\Asset\Image && stripos($image->getMimetype(), "svg") !== false) {
//    echo file_get_contents(PIMCORE_ASSET_DIRECTORY.$image->getRealFullPath());
//} else if ($enableImageloader) {
//    $altText = ($hasImageBlock) ? $imageBlock->getAlt() : $image->getProperty("alt") ?? $image->getMetadata("alt");
//    echo '<div style="position:relative;overflow:hidden;" class="image-loader"';
//    if (!empty($sizeSelector)) {
//        echo ' data-sizeSelector="'.$sizeSelector.'"';
//    }
//    $thumbConfig = (is_array($view->thumbnail) ? $view->thumbnail : []);
//    $widths = [320, 480, 768, 1024, 1280, 1920];
//    $emptyImageThumbnail = null;
//    $imageSizes = [];
//    if ($hasImageBlock) {
//        if (is_array($thumbnailNames) && count($thumbnailNames) > 0) {
//            foreach ($thumbnailNames as $w => $thumbnailName) {
//                $thumbnail = $imageBlock->getThumbnail($thumbnailName);
//                if (is_null($emptyImageThumbnail)) $emptyImageThumbnail = $thumbnail;
//                $imageSizes[] = $thumbnail.' '.$w;
//            }
//        } else {
//            foreach ($widths as $w) {
//                if (isset($sizesOptions[$w])) {
//                    $image = $sizesOptions[$w]['imageTag'];
//                    $thumbnail = $image->getThumbnail(getThumbnailConfig($thumbConfig, $w));
//                    if (is_null($emptyImageThumbnail)) $emptyImageThumbnail = $thumbnail;
//                    $imageSizes[] = $thumbnail.' '.$w;
//                } else {
//                    $thumbnail = $imageBlock->getThumbnail(getThumbnailConfig($thumbConfig, $w));
//                    if (is_null($emptyImageThumbnail)) $emptyImageThumbnail = $thumbnail;
//                    $imageSizes[] = $thumbnail.' '.$w;
//                }
//            }
//        }
//    } else {
//        if (is_array($thumbnailNames) && count($thumbnailNames) > 0) {
//            foreach ($thumbnailNames as $w => $thumbnailName) {
//                $thumbnail = $imageBlock->getThumbnail($thumbnailName);
//                if (is_null($emptyImageThumbnail)) $emptyImageThumbnail = $thumbnail;
//                $imageSizes[] = $thumbnail.' '.$w;
//            }
//        } else {
//            foreach ($widths as $w) {
//                $thumbnail = $image->getThumbnail(getThumbnailConfig($thumbConfig, $w));
//                if (is_null($emptyImageThumbnail)) $emptyImageThumbnail = $thumbnail;
//                $imageSizes[] = $thumbnail.' '.$w;
//            }
//        }
//    }
//    echo ' data-loader="'.join(",", $imageSizes).'"';
//    echo ($isBackgroundImage ? ' data-loader-bg="true"' : '').'>';
//    if (!$isBackgroundImage || !empty($imageCssClass)) {
//        if ($emptyImageThumbnail instanceof \Pimcore\Model\Asset\Image\Thumbnail) {
//            echo $emptyImageThumbnail->getHtml(["class" => "img-fluid ".$imageCssClass], ["srcset", "width", "height"]);
//        } else {
//            echo '<img class="img-fluid '.$imageCssClass.'" src="'.$imageSizes[0].'" alt="'.$altText.'" />';
//        }
//    }
//    if (!empty($hotspots)) {
//        getHotspotLinks($imageBlock, $hotspots);
//    }
//    echo '</div>';
//} elseif ($enableLazyload) {
//    if ($view->thumbnail) {
//        $this->thumbnailConfig = $view->thumbnail;
//    }
//    if ($hasImageBlock) {
//        $thumb = $imageBlock->getThumbnail($this->thumbnailConfig ?? array("width" => 1024), false);
//    } else {
//        $thumb = $image->getThumbnail($this->thumbnailConfig ?? array("width" => 1024), false);
//    }
//    echo '<img class="lazy '.($this->cssClass ?? "img-fluid").'" src="/bundles/imageloader/empty.png" data-src="'.$thumb.'" width="'.$thumb->getWidth().'" height="'.$thumb->getHeight().'" />';
//    if (!empty($hotspots)) {
//        getHotspotLinks($imageBlock, $hotspots);
//    }
//} else {
//    if ($view->thumbnail) {
//        $this->thumbnailConfig = $view->thumbnail;
//    }
//    if ($hasImageBlock) {
//        echo $imageBlock->getThumbnail($this->thumbnailConfig ?? array("width" => 1024))->getHtml(["class" => $this->cssClass ?? "img-fluid"]);
//    } else {
//        echo $image->getThumbnail($this->thumbnailConfig ?? array("width" => 1024))->getHtml(["class" => $this->cssClass ?? "img-fluid"]);
//    }
//    if (!empty($hotspots)) {
//        getHotspotLinks($imageBlock, $hotspots);
//    }
//}
