# Pimcore ImageLoaderBundle
Pimcore 5.x/6.x bundle to automate the minification of images inside a webpage.

## Install and Enable

```php
composer require nambu-ch/pimcore-image-loader
php bin/console pimcore:bundle:enable ImageLoaderBundle
```

## Usage

Load js file inside your layout.
```php
$view->headScript()->appendFile('/bundles/imageloader/js/imageloader.js');
```

Include your Image Editable inside an area like this.

```php
$image = Asset::getById(2);
echo $view->template("ImageLoaderBundle:ImageLoader:ImageView.html.php", [
    "image" => $image,
    "enableImageloader" => true,
]);
```
