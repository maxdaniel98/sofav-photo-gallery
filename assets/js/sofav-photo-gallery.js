var masonry = new MiniMasonry({
    container: '.sofav-photo-gallery-post',
    gutter:5,
    baseWidth: 255
}); 
const lightbox = GLightbox({
    selector: ".sofav-photo-gallery-post .sofav-photo-gallery__item",
    touchNavigation: true,
    loop: true,
    autoplayVideos: true
});


console.log(lightbox)