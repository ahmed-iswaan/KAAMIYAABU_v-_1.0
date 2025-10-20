// Lottie loader for Laravel Blade
// Place this file in public/assets/js/lottie-loader.js

document.addEventListener('DOMContentLoaded', function () {
    var loaderContainer = document.getElementById('lottie-loader');
    if (loaderContainer && window.lottie) {
        var animation = window.lottie.loadAnimation({
            container: loaderContainer,
            renderer: 'svg',
            loop: true,
            autoplay: true,
            path: '/assets/lottiefiles/Loading Dots Blue.json' // Updated to new Lottie JSON
        });
        animation.setSpeed(0.5); // Slow down the animation
    }
});
