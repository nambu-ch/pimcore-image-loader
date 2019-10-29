
var loaderImages = null;

(function ($) {
    $(document).ready(function () {
        loadImages();
        $('map').imageMapResize();
    });

    function loadImages() {
        loaderImages = $(".image-loader");
        loaderImages.each(function () {
            var el = $(this);
            var imgs = el.attr('data-loader').split(",");
            var widths = {};
            for (var i = 0; i < imgs.length; i++) {
                var params = imgs[i].split(" ");
                var w = parseInt(params[1]);
                widths[w] = params[0];
            }
            var sizeEl = (el.is('[data-sizeSelector]')) ? el.closest(el.attr('data-sizeSelector')) : el;
            if (el.is('[data-sizeSelectorAbs]')) {
                sizeEl = $(el.attr('data-sizeSelectorAbs'));
            }
            el.data('sizeSelector', sizeEl);
            el.data('widths', widths);
        });
        loadResponsiveImage();
        window.addEventListener('resize', function() {
            loadResponsiveImage();
        });
    }
})(jQuery);

function loadResponsiveImage() {
    var el, width, widths, found, asBackground;
    var hasChanges = false;
    loaderImages.each(function () {
        el = $(this);
        width = el.data('sizeSelector').width();
        asBackground = el.is('[data-loader-bg]');
        if (el.find('img').is(':visible')) {
            asBackground = false;
        }
        // console.log(width);
        widths = el.data('widths');
        found = false;
        var lastWidth = 0;
        Object.keys(widths).forEach(function(w) {
            lastWidth = w;
            if (w > width && !found) {
                if (changeImage(el, widths, w, asBackground)) {
                    hasChanges = true;
                }
                found = true;
            }
        });
        // load biggest if not found
        if (!found) {
            if (changeImage(el, widths, lastWidth, asBackground)) {
                hasChanges = true;
            }
        }
    });
    setTimeout(function() {
        if (hasChanges) $(document).trigger("images-resized");
    }, 200);
}

function changeImage(imageEl, widths, width, asBackground) {
    if (asBackground) {
        if (imageEl.css('background-image') != 'url('+widths[width]+')') {
            imageEl.css('background-image', 'url('+widths[width]+')');
            return true;
        }
    } else {
        if (imageEl.find('>img').attr('src') != widths[width]) {
            imageEl.find('>img').attr('src', widths[width]);
            return true;
        }
    }
    return false;
}

/*! Image Map Resizer
 *  Desc: Resize HTML imageMap to scaled image.
 *  Copyright: (c) 2014-15 David J. Bradshaw - dave@bradshaw.net
 *  License: MIT
 */

(function(){
    'use strict';

    function scaleImageMap(){

        function resizeMap() {
            function resizeAreaTag(cachedAreaCoords,idx){
                function scale(coord){
                    var dimension = ( 1 === (isWidth = 1-isWidth) ? 'width' : 'height' );
                    return Math.floor(Number(coord) * scallingFactor[dimension]);
                }

                var isWidth = 0;

                areas[idx].coords = cachedAreaCoords.split(',').map(scale).join(',');
            }

            var scallingFactor = {
                width  : image.width  / image.naturalWidth,
                height : image.height / image.naturalHeight
            };

            cachedAreaCoordsArray.forEach(resizeAreaTag);
        }

        function getCoords(e){
            //Normalize coord-string to csv format without any space chars
            return e.coords.replace(/ *, */g,',').replace(/ +/g,',');
        }

        function debounce() {
            clearTimeout(timer);
            timer = setTimeout(resizeMap, 250);
        }

        function start(){
            if ((image.width !== image.naturalWidth) || (image.height !== image.naturalHeight)) {
                resizeMap();
            }
        }

        function addEventListeners(){
            image.addEventListener('onload',  resizeMap, false); //Detect late image loads in IE11
            window.addEventListener('focus',  resizeMap, false); //Cope with window being resized whilst on another tab
            window.addEventListener('resize', debounce,  false);
            document.addEventListener('fullscreenchange', resizeMap,  false);
        }

        function beenHere(){
            return ('function' === typeof map._resize);
        }

        function setup(){
            areas                 = map.getElementsByTagName('area');
            cachedAreaCoordsArray = Array.prototype.map.call(areas, getCoords);
            image                 = document.querySelector('img[usemap="#'+map.name+'"]');
            map._resize           = resizeMap; //Bind resize method to HTML map element
        }

        var
        /*jshint validthis:true */
            map   = this,
            areas = null, cachedAreaCoordsArray = null, image = null, timer = null;

        if (!beenHere()){
            setup();
            addEventListeners();
            start();
        } else {
            map._resize(); //Already setup, so just resize map
        }
    }



    function factory(){
        function init(element){
            if(!element.tagName) {
                throw new TypeError('Object is not a valid DOM element');
            } else if ('MAP' !== element.tagName.toUpperCase()) {
                throw new TypeError('Expected <MAP> tag, found <'+element.tagName+'>.');
            }

            scaleImageMap.call(element);
        }

        return function imageMapResizeF(target){
            switch (typeof(target)){
                case 'undefined':
                case 'string':
                    Array.prototype.forEach.call(document.querySelectorAll(target||'map'),init);
                    break;
                case 'object':
                    init(target);
                    break;
                default:
                    throw new TypeError('Unexpected data type ('+typeof target+').');
            }
        };
    }

    if (typeof define === 'function' && define.amd) {
        define([],factory);
    } else if (typeof module === 'object' && typeof module.exports === 'object'){
        module.exports = factory(); //Node for browserfy
    } else {
        window.imageMapResize = factory();
    }


    if('jQuery' in window) {
        jQuery.fn.imageMapResize = function $imageMapResizeF(){
            return this.filter('map').each(scaleImageMap).end();
        };
    }

})();
