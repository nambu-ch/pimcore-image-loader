<?php
namespace ImageLoaderBundle\Service;

use Pimcore\Model\DataObject\ClassDefinition;
use Pimcore\Model\DataObject\Data;
use Pimcore\Model\Document;
use Pimcore\Model\Asset;
use Pimcore\Model\Document\Editable;

class ImageLoaderTwigExtensions extends \Twig\Extension\AbstractExtension {

    private $disableCacheBuster = false;
    private $widths = [375, 578, 768, 992, 1400, 1920];

    public function __construct($disableCacheBuster = false) {
        $this->disableCacheBuster = $disableCacheBuster;
    }

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
        if ($asset instanceof Editable\Image) {
            return $this->imageloaderFromBlock($asset, $options);
        }
        if ($asset instanceof Data\Hotspotimage) {
            return $this->imageloaderFromObjectBlock($asset, $options);
        }
        return 'First Parameter is object of wrong type, must be Pimcore\Model\Asset\Image, Pimcore\Model\Document\Editable\Image or Pimcore\Model\DataObject\Data\Hotspotimage.';
    }

    public function imageloaderFromAsset(Asset\Image $asset, array $options = []) {
        $emptyImageThumbnail = null;
        $imageSizes = $this->getImageSizeConfig($asset, $options, $emptyImageThumbnail, $asset->getModificationDate());
        $options["imageSizes"] = $imageSizes;
        $options["emptyImageThumbnail"] = $options["emptyImageThumbnail"] ?? $emptyImageThumbnail;
        $options["objectPosition"] = $this->getImageObjectPosition($asset);

        return $this->imageloaderFromOptions($options);
    }

    public function imageloaderFromBlock(Editable\Image $imageBlock, array $options = []) {
        $emptyImageThumbnail = null;
        $imageSizes = $this->getImageSizeConfig($imageBlock, $options, $emptyImageThumbnail, $imageBlock->getImage()->getModificationDate());
        $options["imageSizes"] = $imageSizes;
        $options["emptyImageThumbnail"] = $options["emptyImageThumbnail"] ?? $emptyImageThumbnail;
        $options["hotspots"] = $imageBlock->getHotspots();
        $options["imageBlock"] = $imageBlock;
        $options["altText"] = $imageBlock->getAlt();
        $options["objectPosition"] = $this->getImageObjectPosition($imageBlock->getImage());

        return $this->imageloaderFromOptions($options);
    }

    public function imageloaderFromObjectBlock(Data\Hotspotimage $imageBlock, array $options = []) {
        $emptyImageThumbnail = null;
        $imageSizes = $this->getImageSizeConfig($imageBlock, $options, $emptyImageThumbnail, $imageBlock->getImage()->getModificationDate());
        $options["imageSizes"] = $imageSizes;
        $options["emptyImageThumbnail"] = $options["emptyImageThumbnail"] ?? $emptyImageThumbnail;
        $options["hotspots"] = $imageBlock->getHotspots();
        $options["imageBlock"] = $imageBlock;
        $options["objectPosition"] = $this->getImageObjectPosition($imageBlock->getImage());

        return $this->imageloaderFromOptions($options);
    }

    protected function getImageObjectPosition(Asset\Image $asset) {
        $focalPointY = $asset->getCustomSetting("focalPointY");
        $focalPointX = $asset->getCustomSetting("focalPointX");
        if (empty($focalPointY) && empty($focalPointX)) return null;

        if (empty($focalPointY)) {
            $focalPointY = "center";
        } else {
            $focalPointY = round($focalPointY, 1)."%";
        }
        if (empty($focalPointX)) {
            $focalPointX = "center";
        } else {
            $focalPointX = round($focalPointX, 1)."%";
        }

        return $focalPointX." ".$focalPointY;
    }

    public static function getImageSizes($imageElement, $options) {
        $instance = new ImageLoaderTwigExtensions();
        $emptyImageThumbnail = null;
        return $instance->getImageSizeConfig($imageElement->getImage(), $options, $emptyImageThumbnail);
    }

    protected function getImageSizeConfig($imageElement, $options, &$emptyImageThumbnail, $cacheBusterTs = null) {
        $imageSizes = [];
        $thumbnailNames = $options["thumbnailNames"] ?? null;
        $thumbConfig = $options["thumbnail"] ?? [];
        $widths = $options["widths"] ?? $this->widths;

        if (isset($options["thumbnail"])) {
            $thumbnailConfig = Asset\Image\Thumbnail\Config::getByName($options["thumbnail"]);
            if ($thumbnailConfig != null) {
                $imageSizes = $this->getImagesByThumbnailMedias($imageElement, $thumbnailConfig, $options, $cacheBusterTs);
                return $imageSizes;
            }
        }
        if (is_array($thumbnailNames) && count($thumbnailNames) > 0) {
            foreach ($thumbnailNames as $w => $thumbnailName) {
                $thumbnail = $imageElement->getThumbnail($thumbnailName);
                if (is_null($emptyImageThumbnail)) $emptyImageThumbnail = $thumbnail;
                $imageSizes[] = [
                    'image' => $this->getThumbnailPath($thumbnail, $options, $cacheBusterTs).' '.$w,
                    'size' => $thumbnail->getWidth().'/'.$thumbnail->getHeight().' '.$w,
                ];
            }
        } else {
            foreach ($widths as $w) {
                if (isset($sizesOptions[$w])) {
                    $image = $sizesOptions[$w]['imageTag'];
                    $thumbnail = $image->getThumbnail($this->getThumbnailConfig($thumbConfig, $w));
                    if (is_null($emptyImageThumbnail)) $emptyImageThumbnail = $thumbnail;
                    $imageSizes[] = [
                        'image' => $this->getThumbnailPath($thumbnail, $options, $cacheBusterTs).' '.$w,
                        'size' => $thumbnail->getWidth().'/'.$thumbnail->getHeight().' '.$w,
                    ];
                } else {
                    $thumbnail = $imageElement->getThumbnail($this->getThumbnailConfig($thumbConfig, $w));
                    if (is_null($emptyImageThumbnail)) $emptyImageThumbnail = $thumbnail;
                    $imageSizes[] = [
                        'image' => $this->getThumbnailPath($thumbnail, $options, $cacheBusterTs).' '.$w,
                        'size' => $thumbnail->getWidth().'/'.$thumbnail->getHeight().' '.$w,
                    ];
                }
            }
        }

        return $imageSizes;
    }

    protected function imageloaderFromOptions(array $options) {
        if (!isset($options["isBackgroundImage"])) $options["isBackgroundImage"] = false;

        $html = ['<div style="position:relative;overflow:hidden;" class="image-loader"'];
        if (isset($options["sizeSelector"]) && !empty($options["sizeSelector"])) {
            $html[] = ' data-sizeSelector="'.$options["sizeSelector"].'"';
        }
        $imgPaths = [];
        $imgSizes = [];
        foreach ($options["imageSizes"] as $size => $item) {
            $imgPaths[] = $item["image"];
            $imgSizes[] = $item["size"];
        }
        $html[] = ' data-loader="'.join(",", $imgPaths).'"';
        if ($options["setImageSize"] ?? false) {
            $html[] = ' data-sizes="'.join(",", $imgSizes).'"';
        }
        $html[] = (($options["isBackgroundImage"]) ? ' data-loader-bg="true"' : '').'';
        $html[] = ((isset($options["lazyLoad"]) && $options["lazyLoad"]) ? ' data-lazyload="true"' : '').'';
        $html[] = '>';

        if (!($options["isBackgroundImage"]) || isset($options["imageCssClass"])) {
            if ($options["emptyImageThumbnail"] instanceof Asset\Image\Thumbnail) {
                $html[] = $options["emptyImageThumbnail"]->getImageTag([
                    "imgAttributes" => [
                        "class" => "img-fluid".(isset($options["imageCssClass"]) ? " ".$options["imageCssClass"] : ""),
                    ],
                    "alt"   => $options["altText"] ?? '',
                ],
                    ["srcset", "width", "height"]
                );
            } elseif (!empty($options["emptyImageThumbnail"])) {
                $html[] = '<img class="img-fluid'.(isset($options["imageCssClass"]) ? " ".$options["imageCssClass"] : "").'"'.(!empty($options["objectPosition"]) ? ' style="object-position: '.$options["objectPosition"].'"' : '').' src="'.$options["emptyImageThumbnail"].'" alt="'.$options["altText"].'" />';
            } else {
                $src = explode(" ", $options["imageSizes"][0]["image"]);
                $html[] = '<img class="img-fluid'.(isset($options["imageCssClass"]) ? " ".$options["imageCssClass"] : "").'"'.(!empty($options["objectPosition"]) ? ' style="object-position: '.$options["objectPosition"].'"' : '').' src="'.$src[0].'" alt="'.($options["altText"] ?? '').'" />';
            }
        }
        if (!empty($options["hotspots"])) {
            $html[] = $this->getHotspotLinks($options["imageBlock"], $options["hotspots"]);
        }

        $html[] = '</div>';

        return join("", $html);
    }

    protected function getThumbnailConfig(array $baseConfig, $width) {
        $cfg = array_merge($baseConfig, ["width" => $width]);
        if (array_key_exists("height", $cfg)) {
            $cfg["height"] = $width;
        }
        return $cfg;
    }

    protected function getHotspotLinks($imageBlock, $hotspots) {
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

    protected function getImagesByThumbnailMedias($image, $thumbConfig, array $options, $cacheBusterTs = null) {
        $imageSizes = [];
        $thumb = $image->getThumbnail($thumbConfig, true);
        $imageSizes[2000] = [
            'image' => $this->getThumbnailPath($thumb, $options, $cacheBusterTs).' 2000',
            'size' => $thumb->getWidth().'/'.$thumb->getHeight().' 2000',
        ];

        foreach ($thumbConfig->getMedias() as $mediaQuery => $config) {
            $thumb = null;
            $thumbConfigRes = clone $thumbConfig;
            $thumbConfigRes->selectMedia($mediaQuery);
            $thumbConfigRes->setHighResolution(1);
            $thumb = $image->getThumbnail($thumbConfigRes, true);

            if ($mediaQuery) {
                if (preg_match('/^[\d]+w$/', $mediaQuery)) {
                    // we replace the width indicator (400w) out of the name and build a proper media query for max width
                    $maxWidth = str_replace('w', '', $mediaQuery);
                    $sourceTagAttributes['media'] = '(max-width: '.$maxWidth.'px)';
                    $imageSizes[intval($maxWidth)] = [
                        'image' => $this->getThumbnailPath($thumb, $options, $cacheBusterTs).' '.$maxWidth,
                        'size' => $thumb->getWidth().'/'.$thumb->getHeight().' '.$maxWidth,
                    ];
                } else if (preg_match('/([\d]+)px/', $mediaQuery, $m)) {
                    $size = $m[1];
                    $imageSizes[intval($size)] = [
                        'image' => $this->getThumbnailPath($thumb, $options, $cacheBusterTs).' '.intval($size),
                        'size' => $thumb->getWidth().'/'.$thumb->getHeight().' '.intval($size),
                    ];
                }
            }
        }
        ksort($imageSizes);
        return array_values($imageSizes);
    }

    protected function getThumbnailPath($thumbnailPath, array $options, $cacheBusterTs = null) {
        if ($this->disableCacheBuster === true || (isset($options["disableCacheBuster"]) && $options["disableCacheBuster"] === true)) {
            return $thumbnailPath;
        }
        return ($cacheBusterTs != null ? '/cache-buster-'.$cacheBusterTs : '').$thumbnailPath;
    }

}
