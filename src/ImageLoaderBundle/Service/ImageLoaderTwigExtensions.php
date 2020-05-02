<?php
namespace ImageLoaderBundle\Service;

use Pimcore\Model\Document;
use Pimcore\Model\Asset;
use Pimcore\Model\Document\Tag;

class ImageLoaderTwigExtensions extends \Twig\Extension\AbstractExtension {

    private $widths = [320, 480, 768, 1024, 1280, 1920];

    public function getFunctions() {
        return [
            new \Twig\TwigFunction('imageloader', [$this, 'imageloader']),
            new \Twig\TwigFunction('imageloader_asset', [$this, 'imageloaderFromAsset']),
            new \Twig\TwigFunction('imageloader_block', [$this, 'imageloaderFromBlock']),
        ];
    }

    public function imageloader($asset, array $options = []) {
        if ($asset instanceof Asset\Image) {
            return $this->imageloaderFromAsset($asset, $options);
        }
        if ($asset instanceof Tag\Image) {
            return $this->imageloaderFromBlock($asset, $options);
        }
        return 'First Parameter is object of wrong type, must be Pimcore\Model\Asset\Image or Pimcore\Model\Document\Tag\Image.';
    }

    public function imageloaderFromAsset(Asset\Image $asset, array $options = []) {
        $emptyImageThumbnail = null;
        $imageSizes = $this->getImageSizeConfig($asset, $options, $emptyImageThumbnail);
        $options["imageSizes"] = $imageSizes;
        $options["emptyImageThumbnail"] = $emptyImageThumbnail;

        return $this->imageloaderFromOptions($options);
    }

    public function imageloaderFromBlock(Tag\Image $imageBlock, array $options = []) {
        $emptyImageThumbnail = null;
        $imageSizes = $this->getImageSizeConfig($imageBlock, $options, $emptyImageThumbnail);
        $options["imageSizes"] = $imageSizes;
        $options["emptyImageThumbnail"] = $emptyImageThumbnail;
        $options["hotspots"] = $imageBlock->getHotspots();
        $options["imageBlock"] = $imageBlock;

        return $this->imageloaderFromOptions($options);
    }

    private function getImageSizeConfig($imageElement, $options, &$emptyImageThumbnail) {
        $imageSizes = [];
        $thumbnailNames = isset($options["thumbnailNames"]) ? $options["thumbnailNames"] : null;
        $thumbConfig = (is_array($options["thumbnail"]) ? $options["thumbnail"] : []);
        $widths = (is_array($options["widths"]) ? $options["widths"] : $this->widths);

        if (is_array($thumbnailNames) && count($thumbnailNames) > 0) {
            foreach ($thumbnailNames as $w => $thumbnailName) {
                $thumbnail = $imageElement->getThumbnail($thumbnailName);
                if (is_null($emptyImageThumbnail)) $emptyImageThumbnail = $thumbnail;
                $imageSizes[] = $thumbnail.' '.$w;
            }
        } else {
            foreach ($widths as $w) {
                if (isset($sizesOptions[$w])) {
                    $image = $sizesOptions[$w]['imageTag'];
                    $thumbnail = $image->getThumbnail($this->getThumbnailConfig($thumbConfig, $w));
                    if (is_null($emptyImageThumbnail)) $emptyImageThumbnail = $thumbnail;
                    $imageSizes[] = $thumbnail.' '.$w;
                } else {
                    $thumbnail = $imageElement->getThumbnail($this->getThumbnailConfig($thumbConfig, $w));
                    if (is_null($emptyImageThumbnail)) $emptyImageThumbnail = $thumbnail;
                    $imageSizes[] = $thumbnail.' '.$w;
                }
            }
        }

        return $imageSizes;
    }

    private function imageloaderFromOptions(array $options) {
        $html = ['<div style="position:relative;overflow:hidden;" class="image-loader"'];
        if (isset($options["sizeSelector"]) && !empty($options["sizeSelector"])) {
            $html[] = ' data-sizeSelector="'.$options["sizeSelector"].'"';
        }
        $html[] = ' data-loader="'.join(",", $options["imageSizes"]).'"';
        $html[] = (isset($options["isBackgroundImage"]) ? ' data-loader-bg="true"' : '').'';
        $html[] = '>';

        if (!isset($options["isBackgroundImage"]) || !isset($options["imageCssClass"])) {
            if ($options["emptyImageThumbnail"] instanceof Asset\Image\Thumbnail) {
                $html[] = $options["emptyImageThumbnail"]->getHtml(["class" => "img-fluid ".$options["imageCssClass"]], ["srcset", "width", "height"]);
            } else {
                $html[] = '<img class="img-fluid '.$options["imageCssClass"].'" src="'.$options["imageSizes"][0].'" alt="'.($options["altText"] ?? '').'" />';
            }
        }
        if (!empty($options["hotspots"])) {
            $this->getHotspotLinks($options["imageBlock"], $options["hotspots"]);
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
            if ($imageBlock->getCropTop() != null) {
                // @todo: works only if the whole image is displayed
                $area["top"] = ($area["top"] - $imageBlock->getCropTop());
                $area["left"] = ($area["left"] - $imageBlock->getCropLeft());
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



}
