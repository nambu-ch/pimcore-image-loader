# Pimcore ImageLoaderBundle
Pimcore 5.x/6.x bundle to automate the minification of images inside a webpage.

Automatically generates all needed thumbnails and loads them in relation to the
width of the surrounding html-element. The smallest image will be loaded as the default.
Default image sizes are ```[320, 480, 768, 1024, 1280, 1920]```.

## Install and Enable

```bash
composer require nambu-ch/pimcore-image-loader
php bin/console pimcore:bundle:enable ImageLoaderBundle
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

```php
$imageBlock = $view->image("image");
if ($editmode) {
    echo $imageBlock;
} else {
    echo $view->template("ImageLoaderBundle:ImageLoader:ImageView.html.php", [
        "image" => $imageBlock,
    ]);
}
// or
$image = Asset::getById(2);
echo $view->template("ImageLoaderBundle:ImageLoader:ImageView.html.php", [
    "image" => $image,
]);
```
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

### Available options

Following options are available:

| Name                | Type    | Description                                                                                                                |
|---------------------|---------|----------------------------------------------------------------------------------------------------------------------------|
| `isBackgroundImage` | boolean | Set to true to load image as css background, instead of img-tag.                                                           |
| `imageCssClass`     | string  | A CSS class to apply to the image.                                                                                         |
| `thumbnailNames`    | array   | List of size => thumbnail-names to generate the different sizes. e.g. ```[ 320 => 'thumb-small', 1024 => 'thumb-big' ]```  |
| `sizeSelector`      | string  | jQuery CSS selector to a html element which will be used for determining the size. e.g. '.some-element'                    |
| `widths`            | array   | List of thumbnail widths to override default sizes e.g. ```[ 480, 1024, 1920 ]```                                          |
| `sizesOptions`      | array   | List of options e.g. ```[ 480 => [ 'size' => 480, 'imageTag' => $view->image('image-480') ]``` ]                           |

### Advanced usage

```sizeSelector``` Option can be used to switch from background image to img-tag, set ```isBackgroundImage``` to true and define 
```sizeSelector => 'd-block d-md-none'```. If so the img-tag is shown on small sizes and a background from md-breakpoint upwards.
