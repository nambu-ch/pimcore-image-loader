<?php
namespace ImageLoaderBundle\Service;

use Pimcore\Model\DataObject\ClassDefinition;
use Pimcore\Model\DataObject\Data;
use Pimcore\Model\Document;
use Pimcore\Model\Asset;
use Pimcore\Model\Document\Editable;
use Pimcore\Model\Document\PageSnippet;
use Pimcore\Model\Tool\TmpStore;
use Pimcore\Templating\Renderer\EditableRenderer;

class ImageLoaderTwigExtensions extends \Twig\Extension\AbstractExtension {

    private EditableRenderer $editableRenderer;
    private $disableCacheBuster = false;
    private $widths = [375, 578, 768, 992, 1400, 1920];

    public function __construct(EditableRenderer $editableRenderer, $disableCacheBuster = false) {
        $this->disableCacheBuster = $disableCacheBuster;
        $this->editableRenderer = $editableRenderer;
    }

    public function getFunctions() {
        return [
            new \Twig\TwigFunction('imageloader', [$this, 'imageloader'], ['needs_context' => true, 'is_safe' => ['html']]),
            new \Twig\TwigFunction('imageloader_asset', [$this, 'imageloaderFromAsset'], ['is_safe' => ['html']]),
            new \Twig\TwigFunction('imageloader_block', [$this, 'imageloaderFromBlock'], ['is_safe' => ['html']]),
            new \Twig\TwigFunction('imageloader_editable', [$this, 'imageloaderEditable'], ['needs_context' => true, 'is_safe' => ['html']]),
        ];
    }

    public function imageloaderEditable(array $context, string $name, array $options = []) {
        $document = $context['document'];
        $editmode = $context['editmode'];
        if (!($document instanceof PageSnippet)) {
            return '';
        }

        if ($editmode) {
            return $this->editableRenderer->render($document, 'image', $name, $options, $editmode);
        } else {
            $editable = $this->editableRenderer->getEditable($document, 'image', $name, $options, $editmode);
            if ($editable instanceof Editable\Image && !$editable->isEmpty()) {
                return $this->imageloaderFromBlock($editable, $options);
            }
        }
    }

    public function imageloader(array $context, $asset, array $options = []) {
        if ($asset instanceof Asset\Image) {
            return $this->imageloaderFromAsset($asset, $options);
        }
        if ($asset instanceof Editable\Image) {
            return $this->imageloaderFromBlock($asset, $options);
        }
        if ($asset instanceof Data\Hotspotimage) {
            return $this->imageloaderFromObjectBlock($asset, $options);
        }
        if (is_string($asset)) {
            return $this->imageloaderEditable($context, $asset, $options);
        }
        return 'First Parameter is of wrong type, must be Pimcore\Model\Asset\Image, Pimcore\Model\Document\Editable\Image or Pimcore\Model\DataObject\Data\Hotspotimage or string.';
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
        $imageSizes = $this->getImageSizeConfig($imageBlock, $options, $emptyImageThumbnail, $imageBlock->getImage()?->getModificationDate());
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
        $imageSizes = $this->getImageSizeConfig($imageBlock, $options, $emptyImageThumbnail, $imageBlock->getImage()?->getModificationDate());
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
            if (is_null($thumbnailConfig)) {
                throw new \Exception("Thumbnail with name '".$options["thumbnail"]."' does not exist");
            }
            $imageSizes = $this->getImagesByThumbnailMedias($imageElement, $thumbnailConfig, $options, $cacheBusterTs);
            return $imageSizes;
        }
        if (is_array($thumbnailNames) && count($thumbnailNames) > 0) {
            foreach ($thumbnailNames as $w => $thumbnailName) {
                $thumbnail = $imageElement->getThumbnail($thumbnailName);
                if (is_null($emptyImageThumbnail)) $emptyImageThumbnail = $thumbnail;
                $imageSizes[] = [
                    'image' => $this->getThumbnailPath($thumbnail, $options, $cacheBusterTs).' '.$w,
                    'size'  => $thumbnail->getWidth().'/'.$thumbnail->getHeight().' '.$w,
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
                        'size'  => $thumbnail->getWidth().'/'.$thumbnail->getHeight().' '.$w,
                    ];
                } else {
                    $thumbnail = $imageElement->getThumbnail($this->getThumbnailConfig($thumbConfig, $w));
                    if (is_null($emptyImageThumbnail)) $emptyImageThumbnail = $thumbnail;
                    $imageSizes[] = [
                        'image' => $this->getThumbnailPath($thumbnail, $options, $cacheBusterTs).' '.$w,
                        'size'  => $thumbnail->getWidth().'/'.$thumbnail->getHeight().' '.$w,
                    ];
                }
            }
        }

        return $imageSizes;
    }

    protected function imageloaderFromOptions(array $options) {
        $imgPaths = [];
        $imgSizes = [];
        foreach ($options["imageSizes"] as $size => $item) {
            $imgPaths[] = $item["image"];
            $imgSizes[] = $item["size"];
        }

        $attrs = [
            'class'       => 'image-loader',
            'style'       => 'position:relative;overflow:hidden',
            'data-loader' => join(",", $imgPaths)
        ];

        if ($options["isBackgroundImage"] ?? false) $attrs['data-loader-bg'] = "true";
        if (!empty($options['class'])) $attrs['class'] .= ' '.$options['class'];
        if (!empty($options['lazyLoad'])) $attrs['data-lazyload'] = "true";
        if (!empty($options['setImageSize'])) $attrs['data-sizes'] = join(",", $imgSizes);
        if (!empty($options['sizeSelector'])) $attrs['data-sizeSelector'] = $options["sizeSelector"];

        $html = [];
        $html[] = '<div '.array_to_html_attribute_string($attrs).'>';

        if (!($options["isBackgroundImage"] ?? false) || isset($options["imageCssClass"])) {
            $attrs = [
                'class' => 'img-fluid'
            ];
            if (!empty($options["imageCssClass"])) $attrs['class'] .= ' '.$options["imageCssClass"];
            if (!empty($options["altText"])) $attrs['alt'] = $options["altText"];
            if (!empty($options["objectPosition"])) $attrs['style'] = 'object-position: '.$options["objectPosition"];

            if ($options["emptyImageThumbnail"] instanceof Asset\Image\Thumbnail) {
                $attrs['src'] = $options["emptyImageThumbnail"]->getPath();
            } elseif (!empty($options["emptyImageThumbnail"])) {
                $attrs['src'] = $options["emptyImageThumbnail"];
            } elseif (isset($options["imageSizes"][0]["image"])) {
                $attrs['src'] = explode(" ", $options["imageSizes"][0]["image"]);
            }

            $html[] = '<img '.array_to_html_attribute_string($attrs).'>';
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
            'size'  => $thumb->getWidth().'/'.$thumb->getHeight().' 2000',
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
                        'size'  => $thumb->getWidth().'/'.$thumb->getHeight().' '.$maxWidth,
                    ];
                } else if (preg_match('/([\d]+)px/', $mediaQuery, $m)) {
                    $size = $m[1];
                    $imageSizes[intval($size)] = [
                        'image' => $this->getThumbnailPath($thumb, $options, $cacheBusterTs).' '.intval($size),
                        'size'  => $thumb->getWidth().'/'.$thumb->getHeight().' '.intval($size),
                    ];
                }
            }
        }
        ksort($imageSizes);
        return array_values($imageSizes);
    }

    protected function getThumbnailPath($thumbnailPath, array $options, $cacheBusterTs = null) {
        if ($thumbnailPath instanceof Asset\Image\Thumbnail) {
            // generate temp config
            $configId = 'thumb_' . $thumbnailPath->getAsset()->getId() . '__' . md5($thumbnailPath);
            TmpStore::add($configId, $thumbnailPath->getConfig(), 'thumbnail_deferred');
        }
        if ($this->disableCacheBuster === true || (isset($options["disableCacheBuster"]) && $options["disableCacheBuster"] === true)) {
            return $thumbnailPath;
        }
        return ($cacheBusterTs != null ? '/cache-buster-'.$cacheBusterTs : '').$thumbnailPath;
    }

}
