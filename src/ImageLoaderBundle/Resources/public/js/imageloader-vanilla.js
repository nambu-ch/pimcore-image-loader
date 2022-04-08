class ImageLoader {
    constructor(imageElement) {
        this.imageElement = imageElement;
        this.imageTag = this.imageElement.querySelector('img');
        this.imagePaths = {};
        this.imageSizes = {};
        this.sizeSelector = imageElement;
        //
        let images = imageElement.dataset.loader.split(',');
        for (var i = 0; i < images.length; i++) {
            var params = images[i].split(" ");
            var w = parseInt(params[1]);
            this.imagePaths[w] = params[0];
        }
        //
        let imageSizes = (imageElement.dataset.sizes || '').split(',');
        for (var i = 0; i < imageSizes.length; i++) {
            var params = imageSizes[i].split(" ");
            var w = parseInt(params[1]);
            this.imageSizes[w] = params[0].split("/");
        }
        //
        if (imageElement.dataset.sizeSelector) {
            this.sizeSelector = imageElement.closest(imageElement.dataset.sizeSelector) || this.sizeSelector;
        }

        window.addEventListener('resize', () => {
            if (this.imageElement.classList.contains('inited')) {
                this.resizeImage();
            }
        });

        if (this.imageElement.dataset.lazyload) {
            var imageObserver = new IntersectionObserver((entries, observer) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        this.imageElement.classList.add('inited');
                        this.resizeImage();
                        imageObserver.unobserve(entry.target);
                    }
                });
            });
            imageObserver.observe(this.imageElement);
        } else {
            this.imageElement.classList.add('inited');
            this.resizeImage();
        }
    }

    resizeImage() {
        var width = this.sizeSelector.clientWidth;
        var asBackground = this.imageElement.dataset.loaderBg;
        if (this.isImageHidden()) {
            asBackground = true;
        }
        var found = false,
            hasChanges = false,
            lastWidth = 0;

        Object.keys(this.imagePaths).forEach((w) => {
            lastWidth = w;
            if (w > width && !found) {
                if (this.changeImage(w, asBackground)) {
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
    }

    changeImage(width, asBackground) {
        if (asBackground) {
            if (this.imageElement.style.backgroundImage !== 'url(' + this.imagePaths[width] + ')') {
                this.imageElement.style.backgroundImage = 'url(' + this.imagePaths[width] + ')';
                return true;
            }
            return false;
        }
        //
        var hasChangedImg = this.imageTag.src !== this.imagePaths[width];
        if (hasChangedImg) {
            this.imageTag.src = this.imagePaths[width];
            if (this.imageElement.dataset.sizes) {
                this.imageTag.width = this.imageSizes[width][0];
                this.imageTag.height = this.imageSizes[width][1];
            }
            return true;
        }
        return false;
    }

    isImageHidden() {
        var style = window.getComputedStyle(this.imageTag);
        return (style.display === 'none');
    }
}

window.addEventListener('DOMContentLoaded', function () {
    Array.prototype.forEach.call(document.querySelectorAll('.image-loader'), (el) => {
        new ImageLoader(el);
    });
});
