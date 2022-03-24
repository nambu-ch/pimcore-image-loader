# Pimcore ImageLoaderBundle

Pimcore 10.x bundle to automate the minification of images inside a webpage.

Automatically generates all needed thumbnails and loads them in relation to the width of the surrounding html-element. The smallest image will be loaded as the default. Default image sizes are ```[320, 480, 768, 1024, 1280, 1920]```.

## Install and Enable

```bash
composer require nambu-ch/pimcore-image-loader
php bin/console pimcore:bundle:enable ImageLoaderBundle
```

## Dependencies
This library needs Bootstrap and jQuery to work.

### Get rid of bootstrap
As an easy fix you can add the following css definition to your own style, so you don't need to include bootstrap:

```css
.img-fluid {
    max-width: 100%;
    height: auto;
}
```

## Usage

Load js file inside your layout.

```php
$view->headScript()->appendFile('/bundles/imageloader/js/imageloader.js');
```

```twig
{% do pimcore_head_script().appendFile(asset('/bundles/imageloader/js/imageloader.js')) %}
```

### Example

```twig
{% if editmode %}
    {{ pimcore_image("image", { width: 300 }) }}
{% else %}
    {% if not pimcore_image("image").isEmpty() %}
        {{ imageloader(pimcore_image("image"), { options... })|raw }}
    {% endif %}
{% endif %}
// or
{% set asset = pimcore_asset(2) %}
{{ imageloader(asset, { options... })|raw }}
```

### Force imageloader to recalculate

The imageloader listens to the window resize event and automaticaly loads the best image, but sometimes images appear and need an manual recalculation of the best image size, for example when an accordion opens. In combination with the bootstrap event, this is done like this:

```js
$('.accordion-collapse').on('show.bs.collapse', function () {
    var images = $(this).find(".image-loader");
    setTimeout(function () {
        images.imageLoader('onResized');
    }, 1);
}).on('shown.bs.collapse', function () {
    $(this).find(".image-loader").imageLoader('onResized');
});
```

### Cache Buster

CacheBuster is enabled to all images by default. It takes the modification date of the image asset to refresh cache if needed.
It can be disabled with an option available inside the twig function. To disable CacheBuster globally you can use yml configuration as follows.

```
image_loader:
  cache_buster:
    disabled: true
```

### Available options

Following options are available:

| Name                  | Type                   | Description                                                                                                                |
|-----------------------|------------------------|----------------------------------------------------------------------------------------------------------------------------|
| `isBackgroundImage`   | boolean                | Set to true to load image as css background, instead of img-tag.                                                           |
| `imageCssClass`       | string                 | A CSS class to apply to the image.                                                                                         |
| `thumbnailNames`      | array                  | List of size => thumbnail-names to generate the different sizes. e.g. ```[ 320 => 'thumb-small', 1024 => 'thumb-big' ]```  |
| `sizeSelector`        | string                 | jQuery CSS selector to a html element which will be used for determining the size. e.g. '.some-element'                    |
| `widths`              | array                  | List of thumbnail widths to override default sizes e.g. ```[ 480, 1024, 1920 ]```                                          |
| `sizesOptions`        | array                  | List of options e.g. ```[ 480 => [ 'size' => 480, 'imageTag' => $view->image('image-480') ]``` ]                           |
| `altText`             | string                 | Alt-Text of the image.                                                                                                     |
| `thumbnail`           | string                 | Thumbnail-Name from Pimcore configuration.                                                                                 |
| `emptyImageThumbnail` | string or Asset\Image  | Path to an Image or a Pimcore Asset\Image which is shown at start before imageloader determines the fitting thumbnail      |
| `lazyLoad`            | boolean                | Enable lazy loading via IntersectionObserver                                                                               |
| `disableCacheBuster`  | boolean                | Disable Cache Buster

### Advanced usage

```imageCssClass``` Option can be used to switch from background image to img-tag, set ```isBackgroundImage``` to true and define
```imageCssClass => 'd-block d-md-none'```. If so the img-tag is shown on small sizes and a background from md-breakpoint upwards.

### Using thumbnails with media queries

If the option `thumbnail` is set and the configuration has media queries, those are used for loading the image. Media queries are only used with their px value. So it doesn't matter if you have set min-with or max-width. It will always use the px as max-width. This option can be used together with `isBackgroundImage`, `imageCssClass`, `sizeSelector` and `altText`.
