<?php
namespace ImageLoaderBundle\Service;

use Pimcore\Model\DataObject\ClassDefinition;
use Pimcore\Model\DataObject\Data;
use Pimcore\Model\Document;
use Pimcore\Model\Asset;
use Pimcore\Model\Document\Tag;

class ImageLoaderTwigExtensions extends \Twig\Extension\AbstractExtension {

    private $widths = [320, 480, 768, 1024, 1280, 1920];

    public function getFunctions() {
        return [
            new \Twig\TwigFunction('imageloader', [$this, 'imageloader'], ['is_safe' => ['html']]),
            new \Twig\TwigFunction('imageloader_asset', [$this, 'imageloaderFromAsset'], ['is_safe' => ['html']]),
            new \Twig\TwigFunction('imageloader_block', [$this, 'imageloaderFromBlock'], ['is_safe' => ['html']]),
        ];
    }

    public function imageloader($asset, array $options = []) {
        if ($asset instanceof Asset\Image) {
            return $this->imageloaderFromAsset($asset, $options);
        }
        if ($asset instanceof Tag\Image) {
            return $this->imageloaderFromBlock($asset, $options);
        }
        if ($asset instanceof Data\Hotspotimage) {
            return $this->imageloaderFromObjectBlock($asset, $options);
        }
        return 'First Parameter is object of wrong type, must be Pimcore\Model\Asset\Image, Pimcore\Model\Document\Tag\Image or Pimcore\Model\DataObject\Data\Hotspotimage.';
    }

    public function imageloaderFromAsset(Asset\Image $asset, array $options = []) {
        $emptyImageThumbnail = null;
        $imageSizes = $this->getImageSizeConfig($asset, $options, $emptyImageThumbnail);
        $options["imageSizes"] = $imageSizes;
        $options["emptyImageThumbnail"] = $options["emptyImageThumbnail"] ?? $emptyImageThumbnail;
        if (empty($options["width"])) $options["width"] = $asset->getWidth();
        if (empty($options["height"])) $options["height"] = $asset->getHeight();

        return $this->imageloaderFromOptions($options);
    }

    public function imageloaderFromBlock(Tag\Image $imageBlock, array $options = []) {
        $emptyImageThumbnail = null;
        $imageSizes = $this->getImageSizeConfig($imageBlock, $options, $emptyImageThumbnail);
        $options["imageSizes"] = $imageSizes;
        $options["emptyImageThumbnail"] = $options["emptyImageThumbnail"] ?? $emptyImageThumbnail;
        $options["hotspots"] = $imageBlock->getHotspots();
        $options["imageBlock"] = $imageBlock;
        $options["altText"] = $imageBlock->getAlt();

        if (!$imageBlock->isEmpty()) {
            $options["width"] = $imageBlock->getImage()->getWidth();
            $options["height"] = $imageBlock->getImage()->getHeight();
        }

        return $this->imageloaderFromOptions($options);
    }

    public function imageloaderFromObjectBlock(Data\Hotspotimage $imageBlock, array $options = []) {
        $emptyImageThumbnail = null;
        $imageSizes = $this->getImageSizeConfig($imageBlock, $options, $emptyImageThumbnail);
        $options["imageSizes"] = $imageSizes;
        $options["emptyImageThumbnail"] = $options["emptyImageThumbnail"] ?? $emptyImageThumbnail;
        $options["hotspots"] = $imageBlock->getHotspots();
        $options["imageBlock"] = $imageBlock;

        if (!$imageBlock->isEmpty()) {
            $options["width"] = $imageBlock->getImage()->getWidth();
            $options["height"] = $imageBlock->getImage()->getHeight();
        }

        return $this->imageloaderFromOptions($options);
    }

    public static function getImageSizes($imageElement, $options) {
        $instance = new ImageLoaderTwigExtensions();
        $emptyImageThumbnail = null;
        return $instance->getImageSizeConfig($imageElement->getImage(), $options, $emptyImageThumbnail);
    }

    private function getImageSizeConfig($imageElement, $options, &$emptyImageThumbnail) {
        $imageSizes = [];
        $thumbnailNames = isset($options["thumbnailNames"]) ? $options["thumbnailNames"] : null;
        $thumbConfig = (is_array($options["thumbnail"]) ? $options["thumbnail"] : []);
        $widths = (is_array($options["widths"]) ? $options["widths"] : $this->widths);

        $timestamp = 0;
        if ($imageElement instanceof Asset\Image) $timestamp = $imageElement->getModificationDate() ?? '0';
        if ($imageElement instanceof Document\Editable\Image && !$imageElement->isEmpty()) $timestamp = $imageElement->getImage()->getModificationDate() ?? '0';
        $cacheBuster = '/cache-buster-'.$timestamp;

        if (is_string($options["thumbnail"])) {
            $thumbnailConfig = Asset\Image\Thumbnail\Config::getByName($options["thumbnail"]);
            if ($thumbnailConfig != null && count($thumbnailConfig->getMedias()) > 0) {
                $imageSizes = $this->getImagesByThumbnailMedias($imageElement, $thumbnailConfig);
                return $imageSizes;
            }
        }
        if (is_array($thumbnailNames) && count($thumbnailNames) > 0) {
            foreach ($thumbnailNames as $w => $thumbnailName) {
                $thumbnail = $cacheBuster.$imageElement->getThumbnail($thumbnailName);
                if (is_null($emptyImageThumbnail)) $emptyImageThumbnail = $thumbnail;
                $imageSizes[] = $thumbnail.' '.$w;
            }
        } else {
            foreach ($widths as $w) {
                if (isset($sizesOptions[$w])) {
                    $image = $sizesOptions[$w]['imageTag'];
                    $thumbnail = $cacheBuster.$image->getThumbnail($this->getThumbnailConfig($thumbConfig, $w));
                    if (is_null($emptyImageThumbnail)) $emptyImageThumbnail = $thumbnail;
                    $imageSizes[] = $thumbnail.' '.$w;
                } else {
                    $thumbnail = $cacheBuster.$imageElement->getThumbnail($this->getThumbnailConfig($thumbConfig, $w));
                    if (is_null($emptyImageThumbnail)) $emptyImageThumbnail = $thumbnail;
                    $imageSizes[] = $thumbnail.' '.$w;
                }
            }
        }

        return $imageSizes;
    }

    private function imageloaderFromOptions(array $options) {
        if (!isset($options["isBackgroundImage"])) $options["isBackgroundImage"] = false;

        $html = ['<div style="position:relative;overflow:hidden;" class="image-loader"'];
        if (isset($options["sizeSelector"]) && !empty($options["sizeSelector"])) {
            $html[] = ' data-sizeSelector="'.$options["sizeSelector"].'"';
        }
        $html[] = ' data-loader="'.join(",", $options["imageSizes"]).'"';
        $html[] = (($options["isBackgroundImage"]) ? ' data-loader-bg="true"' : '').'';
        $html[] = (($options["lazyLoad"]) ? ' data-lazyload="true"' : '').'';
        $html[] = '>';

        if (!($options["isBackgroundImage"]) || isset($options["imageCssClass"])) {
            if ($options["emptyImageThumbnail"] instanceof Asset\Image\Thumbnail) {
                $html[] = $options["emptyImageThumbnail"]->getHtml([
                    "class" => "img-fluid ".$options["imageCssClass"],
                    "alt"   => $options["altText"] ?? '',
                ],
                    ["srcset", "width", "height"]
                );
            } elseif (!empty($options["emptyImageThumbnail"])) {
                $html[] = '<img class="img-fluid '.$options["imageCssClass"].'" src="'.$options["emptyImageThumbnail"].'" alt="'.$options["altText"].'"'.($options["width"] ? ' width="'.$options["width"].'"' : '').($options["height"] ? ' height="'.$options["height"].'"' : '').'/>';
            } else {
                $src = explode(" ", $options["imageSizes"][0]);
                $html[] = '<img class="img-fluid '.$options["imageCssClass"].'" src="'.$src[0].'" alt="'.($options["altText"] ?? '').'"'.($options["width"] ? ' width="'.$options["width"].'"' : '').($options["height"] ? ' height="'.$options["height"].'"' : '').'/>';
            }
        }
        if (!empty($options["hotspots"])) {
            $html[] = $this->getHotspotLinks($options["imageBlock"], $options["hotspots"]);
        }

        $html[] = '</div>';

        return join("", $html);
    }

    private function getThumbnailConfig(array $baseConfig, $width) {
        $cfg = array_merge($baseConfig, ["width" => $width]);
        if (array_key_exists("height", $cfg)) {
            $cfg["height"] = $width;
        }
        return $cfg;
    }

    private function getHotspotLinks($imageBlock, $hotspots) {
        $html = [];
        foreach ($hotspots as $area) {
            if (count($area["data"]) != 1) {
                continue;
            }
            //
            if ($imageBlock instanceof ClassDefinition\Data\Hotspotimage) {
                if ($imageBlock->getCropTop() != null) {
                    // @todo: works only if the whole image is displayed
                    $area["top"] = ($area["top"] - $imageBlock->getCropTop());
                    $area["left"] = ($area["left"] - $imageBlock->getCropLeft());
                }
            }
            $linkEl = null;
            if ($area["data"][0]["value"] instanceof Document\Page) {
                /** @var Document\Page $linkEl */
                $linkEl = $area["data"][0]["value"];
            } elseif ($area["data"][0]["value"] instanceof Asset\Image) {
                /** @var Asset\Image $image */
                $linkEl = $area["data"][0]["value"];
            } elseif ($area["data"][0]["value"] instanceof Asset) {
                /** @var Asset\Image $image */
                $linkEl = $area["data"][0]["value"];
            }
            if ($linkEl != null) {
                $html[] = '<a style="position:absolute;display:inline-block;';
                if (\Pimcore::inDebugMode()) {
                    $html[] = 'background-color:rgba(255,255,255,0.4);';
                }
                $html[] = 'top:'.($area["top"]).'%;';
                $html[] = 'left:'.($area["left"]).'%;';
                $html[] = 'width:'.($area["width"]).'%;';
                $html[] = 'height:'.($area["height"]).'%;" href="'.$linkEl->getFullPath().'" data-href="'.$linkEl->getFullPath().'"></a>';
            }
        }

        return join("", $html);
    }

    private function getImagesByThumbnailMedias($image, $thumbConfig) {
        $imageSizes = [];
        $thumb = $image->getThumbnail($thumbConfig, true);
        $imageSizes[2000] = $thumb.' 2000';

        $timestamp = 0;
        if ($image instanceof Asset\Image) $timestamp = $image->getModificationDate() ?? '0';
        if ($image instanceof Document\Editable\Image && !$image->isEmpty()) $timestamp = $image->getImage()->getModificationDate() ?? '0';
        $cacheBuster = '/cache-buster-'.$timestamp;

        foreach ($thumbConfig->getMedias() as $mediaQuery => $config) {
            $thumb = null;
            $thumbConfigRes = clone $thumbConfig;
            $thumbConfigRes->selectMedia($mediaQuery);
            $thumbConfigRes->setHighResolution(1);
            $thumb = $cacheBuster.$image->getThumbnail($thumbConfigRes, true);

            if ($mediaQuery) {
                if (preg_match('/^[\d]+w$/', $mediaQuery)) {
                    // we replace the width indicator (400w) out of the name and build a proper media query for max width
                    $maxWidth = str_replace('w', '', $mediaQuery);
                    $sourceTagAttributes['media'] = '(max-width: '.$maxWidth.'px)';
                    $imageSizes[intval($maxWidth)] = $thumb.' '.$maxWidth;
                } else if (preg_match('/([\d]+)px/', $mediaQuery, $m)) {
                    $size = $m[1];
                    $imageSizes[intval($size)] = $thumb.' '.intval($size);
                }
            }
        }
        ksort($imageSizes);
        return array_values($imageSizes);
    }

}
