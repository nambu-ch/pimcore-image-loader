(function ($) {
    var loaderImages = [];

    var ImageLoader = function (element, options) {
        $.data(element, 'imageloader', this);
        this.element = $(element);

        if (this.element.is('.image-loader') && !this.element.is('.inited')) {
            var imgs = this.element.attr('data-loader').split(",");
            var widths = {};
            for (var i = 0; i < imgs.length; i++) {
                var params = imgs[i].split(" ");
                var w = parseInt(params[1]);
                widths[w] = params[0];
            }
            var sizeEl = (this.element.is('[data-sizeSelector]')) ? this.element.closest(this.element.attr('data-sizeSelector')) : this.element;
            if (this.element.is('[data-sizeSelectorAbs]')) {
                sizeEl = $(this.element.attr('data-sizeSelectorAbs'));
            }
            this._sizeSelector = sizeEl;
            this._widths = widths;
            this.element.addClass('inited');
        }
        if (this.element.is('[data-lazyload]')) {
            var $this = this;
            var imageObserver = new IntersectionObserver(function (entries, observer) {
                entries.forEach(function (entry) {
                    if (entry.isIntersecting) {
                        $this.onResized();
                        imageObserver.unobserve(entry.target);
                    }
                });
            });
            imageObserver.observe(element);
        } else {
            this.onResized();
        }
    };

    ImageLoader.prototype = {
        constructor: ImageLoader,

        onResized: function () {
            var $this = this;
            var width = this._sizeSelector.width();
            var asBackground = this.element.is('[data-loader-bg]');
            if (this.element.find('img').is(':visible')) {
                asBackground = false;
            }
            var widths = this._widths,
                found = false,
                hasChanges = false,
                lastWidth = 0;

            Object.keys(widths).forEach(function (w) {
                lastWidth = w;
                if (w > width && !found) {
                    if ($this.changeImage(w, asBackground)) {
                        hasChanges = true;
                    }
                    found = true;
                }
            });
            // load biggest if not found
            if (!found) {
                if (this.changeImage(lastWidth, asBackground)) {
                    hasChanges = true;
                }
            }
        },

        changeImage: function (width, asBackground) {
            var imageEl = this.element;
            var widths = this._widths;
            if (asBackground) {
                if (imageEl.css('background-image') !== 'url(' + widths[width] + ')') {
                    imageEl.css('background-image', 'url(' + widths[width] + ')');
                    return true;
                }
            } else {
                var hasChangedImg = imageEl.find('>img').attr('src') !== widths[width];
                if (hasChangedImg) {
                    imageEl.find('>img').attr('src', widths[width]);
                    return true;
                }
            }
            return false;
        },

    };

    function opts_from_el(el, prefix) {
        // Derive options from element data-attrs
        var data = $(el).data(),
            out = {}, inkey,
            replace = new RegExp('^' + prefix.toLowerCase() + '([A-Z])');
        prefix = new RegExp('^' + prefix.toLowerCase());

        function re_lower(_, a) {
            return a.toLowerCase();
        }

        for (var key in data)
            if (prefix.test(key)) {
                inkey = key.replace(replace, re_lower);
                out[inkey] = data[key];
            }
        return out;
    }

    $.fn.imageLoader = function (option) {
        var args = Array.apply(null, arguments);
        args.shift();
        var internal_return;
        return this.each(function () {
            var $this = $(this),
                data = $this.data('imageloader'),
                options = typeof option === 'object' && option;
            if (!data) {
                var elopts = opts_from_el(this, 'imageloader'),
                    opts = $.extend({}, defaults, elopts, options);
                data = new ImageLoader(this, opts);
                $this.data('imageloader', data);
                loaderImages.push(data);
            }
            if (typeof option === 'string' && typeof data[option] === 'function') {
                internal_return = data[option].apply(data, args);
            }
        });
        if (internal_return === undefined || internal_return instanceof ImageLoader) {
            return this;
        }

        if (this.length > 1)
            throw new Error('Using only allowed for the collection of a single element (' + option + ' function)');
        else
            return internal_return;
    };

    var defaults = $.fn.imageLoader.defaults = {};

    $(document).ready(function () {
        $('.image-loader').imageLoader();
        $('map').imageMapResize();
    });
    window.addEventListener('resize', function () {
        $.each(loaderImages, function () {
            this.onResized();
        });
    });
}(jQuery));


/*! Image Map Resizer
 *  Desc: Resize HTML imageMap to scaled image.
 *  Copyright: (c) 2014-15 David J. Bradshaw - dave@bradshaw.net
 *  License: MIT
 */

(function () {
    'use strict';

    function scaleImageMap() {

        function resizeMap() {
            function resizeAreaTag(cachedAreaCoords, idx) {
                function scale(coord) {
                    var dimension = (1 === (isWidth = 1 - isWidth) ? 'width' : 'height');
                    return Math.floor(Number(coord) * scallingFactor[dimension]);
                }

                var isWidth = 0;

                areas[idx].coords = cachedAreaCoords.split(',').map(scale).join(',');
            }

            var scallingFactor = {
                width: image.width / image.naturalWidth,
                height: image.height / image.naturalHeight
            };

            cachedAreaCoordsArray.forEach(resizeAreaTag);
        }

        function getCoords(e) {
            //Normalize coord-string to csv format without any space chars
            return e.coords.replace(/ *, */g, ',').replace(/ +/g, ',');
        }

        function debounce() {
            clearTimeout(timer);
            timer = setTimeout(resizeMap, 250);
        }

        function start() {
            if ((image.width !== image.naturalWidth) || (image.height !== image.naturalHeight)) {
                resizeMap();
            }
        }

        function addEventListeners() {
            image.addEventListener('onload', resizeMap, false); //Detect late image loads in IE11
            window.addEventListener('focus', resizeMap, false); //Cope with window being resized whilst on another tab
            window.addEventListener('resize', debounce, false);
            document.addEventListener('fullscreenchange', resizeMap, false);
        }

        function beenHere() {
            return ('function' === typeof map._resize);
        }

        function setup() {
            areas = map.getElementsByTagName('area');
            cachedAreaCoordsArray = Array.prototype.map.call(areas, getCoords);
            image = document.querySelector('img[usemap="#' + map.name + '"]');
            map._resize = resizeMap; //Bind resize method to HTML map element
        }

        var
            /*jshint validthis:true */
            map = this,
            areas = null, cachedAreaCoordsArray = null, image = null, timer = null;

        if (!beenHere()) {
            setup();
            addEventListeners();
            start();
        } else {
            map._resize(); //Already setup, so just resize map
        }
    }


    function factory() {
        function init(element) {
            if (!element.tagName) {
                throw new TypeError('Object is not a valid DOM element');
            } else if ('MAP' !== element.tagName.toUpperCase()) {
                throw new TypeError('Expected <MAP> tag, found <' + element.tagName + '>.');
            }

            scaleImageMap.call(element);
        }

        return function imageMapResizeF(target) {
            switch (typeof (target)) {
                case 'undefined':
                case 'string':
                    Array.prototype.forEach.call(document.querySelectorAll(target || 'map'), init);
                    break;
                case 'object':
                    init(target);
                    break;
                default:
                    throw new TypeError('Unexpected data type (' + typeof target + ').');
            }
        };
    }

    if (typeof define === 'function' && define.amd) {
        define([], factory);
    } else if (typeof module === 'object' && typeof module.exports === 'object') {
        module.exports = factory(); //Node for browserfy
    } else {
        window.imageMapResize = factory();
    }


    if ('jQuery' in window) {
        jQuery.fn.imageMapResize = function $imageMapResizeF() {
            return this.filter('map').each(scaleImageMap).end();
        };
    }

})();
